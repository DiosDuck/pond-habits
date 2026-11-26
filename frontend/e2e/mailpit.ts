import { expect, type APIRequestContext } from '@playwright/test'

// Read app mail in E2E tests through the Mailpit API. Mailpit catches all local and CI mail.
const mailpit = process.env['MAILPIT_URL'] ?? 'http://mailpit:8025'

// The newest link to the address that matches the pattern, for example /\/register\?token=\w+/.
// Mail can come a moment after the click, so it asks again until it finds one.
export async function mailLink(
  request: APIRequestContext,
  email: string,
  pattern: RegExp,
): Promise<string> {
  let link = ''
  await expect
    .poll(async () => {
      const search = await request.get(
        `${mailpit}/api/v1/search?query=${encodeURIComponent(`to:${email}`)}`,
      )
      const found = (await search.json()) as { messages: { ID: string }[] }
      // Newest first. Take the first mail that has a matching link.
      for (const { ID } of found.messages) {
        const message = await request.get(`${mailpit}/api/v1/message/${ID}`)
        const body = (await message.json()) as { Text: string }
        const match = pattern.exec(body.Text)?.[0]
        if (match !== undefined) {
          link = match
          return link
        }
      }
      return ''
    })
    .not.toBe('')
  return link
}
