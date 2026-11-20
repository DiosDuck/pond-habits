// Architecture rules for the frontend (ADR 0009). "make lint" fails on a violation.
/** @type {import('dependency-cruiser').IConfiguration} */
module.exports = {
  forbidden: [
    {
      name: 'no-circular',
      severity: 'error',
      comment: 'Circular imports make the code hard to follow and to test.',
      from: {},
      to: { circular: true },
    },
    {
      name: 'no-orphans',
      severity: 'error',
      comment: 'A file that nothing imports is dead code.',
      from: {
        orphan: true,
        pathNot: [
          '\\.d\\.ts$',
          '(^|/)src/main\\.tsx$',
          '\\.test\\.tsx?$',
          '(^|/)src/test/',
        ],
      },
      to: {},
    },
    {
      name: 'not-to-unresolvable',
      severity: 'error',
      from: {},
      to: { couldNotResolve: true },
    },
    {
      name: 'app-code-not-to-dev-deps',
      severity: 'error',
      comment:
        'App code must not import a devDependency. Tests and test helpers can.',
      from: { path: '^src/', pathNot: ['\\.test\\.tsx?$', '^src/test/'] },
      to: { dependencyTypes: ['npm-dev'], dependencyTypesNot: ['type-only'] },
    },
    {
      name: 'app-code-not-to-tests',
      severity: 'error',
      from: { path: '^src/', pathNot: ['\\.test\\.tsx?$', '^src/test/'] },
      to: { path: ['\\.test\\.tsx?$', '^src/test/'] },
    },
  ],
  options: {
    doNotFollow: { path: 'node_modules' },
    tsPreCompilationDeps: true,
    tsConfig: { fileName: 'tsconfig.json' },
    enhancedResolveOptions: {
      exportsFields: ['exports'],
      conditionNames: ['import', 'require', 'node', 'default'],
    },
  },
}
