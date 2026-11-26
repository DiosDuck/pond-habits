import { expect, test, type APIRequestContext } from '@playwright/test'

import { loginAs } from './auth.ts'

// Invite and registration on the full stack (docs/product/invites.md).
// The mail goes to Mailpit. The test reads the link through the Mailpit API.
const mailpit = process.env['MAILPIT_URL'] ?? 'http://mailpit:8025'

async function inviteLink(
  request: APIRequestContext,
  email: string,
): Promise<string> {
  let link = ''
  await expect
    .poll(async () => {
      const search = await request.get(
        `${mailpit}/api/v1/search?query=${encodeURIComponent(`to:${email}`)}`,
      )
      const found = (await search.json()) as { messages: { ID: string }[] }
      const id = found.messages[0]?.ID
      if (id === undefined) return ''
      const message = await request.get(`${mailpit}/api/v1/message/${id}`)
      const body = (await message.json()) as { Text: string }
      link = /\/register\?token=[A-Za-z0-9]+/.exec(body.Text)?.[0] ?? ''
      return link
    })
    .not.toBe('')
  return link
}

test('an admin invites a person, who registers and lands on Today', async ({
  page,
  request,
}, testInfo) => {
  // A new address for each run and each browser project.
  const email = `invitee-${String(Date.now())}-${testInfo.project.name}@example.com`

  await loginAs(page)
  await page.goto('/admin')
  await page.getByLabel('Email').fill(email)
  await page.getByRole('button', { name: 'Send invite' }).click()
  await expect(page.getByRole('status')).toHaveText(`Invite sent to ${email}.`)

  const link = await inviteLink(request, email)

  // The invitee has no session: start without the admin's cookies.
  await page.context().clearCookies()
  await page.goto(link)
  await expect(page.getByText(email)).toBeVisible()
  const password = `pond e2e ${String(Date.now())} long password`
  await page.getByLabel('Name').fill('Invitee')
  await page.getByLabel('Password', { exact: true }).fill(password)
  await page.getByLabel('Repeat the password').fill(password)
  await page.getByRole('button', { name: 'Create account' }).click()

  await expect(
    page.getByRole('heading', { level: 1, name: 'Today' }),
  ).toBeVisible()

  // A new user is not an admin.
  await page.goto('/more')
  await expect(
    page.getByRole('heading', { level: 1, name: 'More' }),
  ).toBeVisible()
  await expect(page.getByRole('link', { name: 'Admin' })).toHaveCount(0)
})
