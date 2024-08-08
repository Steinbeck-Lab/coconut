import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "COCONUT Docs",
  description: "COCONUT: the COlleCtion of Open Natural prodUcTs",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/logo.png',

    siteTitle: '',

    head: [
      ['link', { rel: "apple-touch-icon", sizes: "180x180", href: "/assets/favicons/apple-touch-icon.png"}],
      ['link', { rel: "icon", type: "image/png", sizes: "32x32", href: "/assets/favicons/favicon-32x32.png"}],
      ['link', { rel: "icon", type: "image/png", sizes: "16x16", href: "/assets/favicons/favicon-16x16.png"}],
      ['link', { rel: "manifest", href: "/assets/favicons/site.webmanifest"}],
      ['link', { rel: "mask-icon", href: "/assets/favicons/safari-pinned-tab.svg", color: "#3a0839"}],
      ['link', { rel: "shortcut icon", href: "/assets/favicons/favicon.ico"}],
      ['meta', { name: "msapplication-TileColor", content: "#3a0839"}],
      ['meta', { name: "msapplication-config", content: "/assets/favicons/browserconfig.xml"}],
      ['meta', { name: "theme-color", content: "#ffffff"}],
    ],

    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guides', link: '/single-submission' },
      { text: 'API', link: '/auth-api' },
      { text: 'About', link: '/about' },
      { text: 'Contact', link: '/contact' }
    ],

    sidebar: [
      {
        text: 'Welcome',
        items: [
          { text: 'Introduction', link: '/introduction' },
          { text: 'Sources', link: '/sources' },
          { text: 'Analysis', link: '/analysis' },
        ]
      },
      {
        text: 'Search',
        items: [
          { text: 'Simple', link: '/simple-search' },
          { text: 'Structure', link: '/structure-search' },
          { text: 'Advanced', link: '/advanced-search' }
        ]
      },
      {
        text: 'Submission Guides',
        items: [
          { text: 'Single Compound Submission', link: '/single-submission' },
          { text: 'Multiple Compound Submission', link: '/multi-submission' },
          { text: 'Programmatic Submission via API', link: '/api-submission' }
        ]
      },
      {
        text: 'API',
        items: [
          { text: 'Auth', link: '/auth-api' },
          { text: 'Search', link: '/search-api' },
          { text: 'Schemas', link: '/schemas-api' },
          { text: 'Download', link: '/download-api' },
          { text: 'Submission', link: '/submission-api' }
        ]
      },
      {
        text: 'Download', link:'/download',
        items: [
        ]
      },
      {
        text: 'Development',
        items: [
          { text: 'Installation', link: '/installation' },
          { text: 'Database Schema', link: '/db-schema' },
          { text: 'License', link: '/license' },
          { text: 'FAQs', link: '/FAQs' },
          { text: 'Issues / Feature requests', link: '/issues' }
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/vuejs/vitepress' }
    ]
  }
})
