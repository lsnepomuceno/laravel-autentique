import { defineConfig } from 'vitepress'
import { release } from './release'
import { pages, sections } from './sidebar'

const current = release()

/**
 * The documentation site, built the way signet-pdf's and laravel-a1-pdf-sign's
 * are.
 *
 * `srcDir` is the parent directory, so the prose stays where the code is and the
 * documents the repository already maintains are published as they are rather
 * than copied. `/docs` is `export-ignore` in `.gitattributes`, so none of this
 * reaches the package a consumer installs.
 *
 * One release line and no archive machinery: there is nothing released yet,
 * and the previous package was a different package with no site of its own.
 */
export default defineConfig({
  title: 'Laravel Autentique',
  description:
    'A Laravel client for the Autentique GraphQL API v2: documents, signers, folders and webhooks.',

  // The project page on GitHub Pages is served under the repository name.
  base: '/laravel-autentique/',

  cleanUrls: true,

  // The home page is `guide/index.md`, published at the site root. A page
  // cannot live inside `.vitepress/`: VitePress excludes that directory from
  // routing, so a document put there is simply never published.
  rewrites: { 'guide/index.md': 'index.md' },
  lastUpdated: true,

  // A link that resolves to nothing fails the build, which is the same rule
  // `tests/Project/SpecTest.php` applies to prose, arriving through the other
  // door. The test asks whether a path exists in the repository; this asks
  // whether it exists as a page.
  //
  // The `releases/` page climbs out of `docs/` deliberately, to name the
  // canonical file GitHub renders. It is the only exception, and it is allowed
  // here rather than by turning the check off.
  ignoreDeadLinks: [/^(\.\/)?(\.\.\/)+CHANGELOG(\.md)?$/],

  markdown: {
    config(md) {
      // The `releases/` page includes CHANGELOG.md from the repository root,
      // whose links are written from there: `docs/decisions/README.md`.
      // Correct where they are authored, and pointing at nothing once the same
      // text is a page under `/releases/`.
      //
      // Rewritten as they render, on that page only. This wraps VitePress's own
      // link rule rather than replacing it, and rewrites before calling it, so
      // the dead-link check still sees the rewritten target.
      const included = ['releases/changelog.md']
      const previous = md.renderer.rules.link_open

      md.renderer.rules.link_open = (tokens, index, options, env, self) => {
        if (included.includes(env?.relativePath)) {
          const token = tokens[index]
          const href = token.attrGet('href')

          if (href) {
            token.attrSet(
              'href',
              href
                .replace(/^(\.\/)?CHANGELOG\.md/, '/releases/changelog.md')
                .replace(/^(\.\/)?docs\//, '/'),
            )
          }
        }

        return previous
          ? previous(tokens, index, options, env, self)
          : self.renderToken(tokens, index, options)
      }
    },
  },

  head: [['meta', { name: 'theme-color', content: '#ff2d20' }]],

  themeConfig: {
    // `activeMatch` on every entry, because the default is an exact match
    // against `link`: without it "Guide" highlights on the one page it points
    // at and goes dark on every other page of the section.
    nav: [
      { text: 'Guide', link: '/guide/getting-started', activeMatch: '^/guide/' },
      { text: 'Specification', link: '/spec/public-api', activeMatch: '^/spec/' },
      { text: 'Decisions', link: '/decisions/README', activeMatch: '^/decisions/' },
      { text: 'History', link: '/history/decision-log', activeMatch: '^/history/' },
      {
        text: 'Releases',
        activeMatch: '^/releases/',
        items: [
          { text: 'Changelog', link: '/releases/changelog' },
          {
            text: 'All releases',
            link: 'https://github.com/lsnepomuceno/laravel-autentique/releases',
          },
        ],
      },
      {
        text: 'Autentique API',
        link: 'https://docs.autentique.com.br/api',
      },
    ],

    // Every section's sidebar is answered by the filesystem, so a page that is
    // added and not listed fails the build rather than going unlinked. The
    // guide grows a group per area as each lands.
    sidebar: {
      '/guide/': sections('guide', [
        { text: 'Getting started', slugs: ['getting-started', 'configuration'] },
        { text: 'Working with it', slugs: ['errors', 'raw-queries', 'commands'] },
      ]),
      '/spec/': [{ text: 'Specification', items: pages('spec') }],
      '/decisions/': [{ text: 'Decisions', items: pages('decisions') }],
      '/history/': [{ text: 'History', items: pages('history') }],
      '/releases/': [{ text: 'Releases', items: pages('releases') }],
    },

    outline: { level: [2, 3] },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/lsnepomuceno/laravel-autentique' },
    ],

    editLink: {
      pattern:
        'https://github.com/lsnepomuceno/laravel-autentique/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },

    search: { provider: 'local' },

    footer: {
      message: `Version ${current.version}${current.released ? '' : ' (not released)'}. Released under the MIT License.`,
      copyright: 'Copyright © Lucas Nepomuceno',
    },
  },
})
