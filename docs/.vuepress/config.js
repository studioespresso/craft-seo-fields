module.exports = {
    base: '/plugins/seo-fields/',
    title: 'Studio Espresso',
    themeConfig: {
        logo: '/icon-vuepress.svg',
        search: true,
        searchMaxSuggestions: 5,
        docsRepo: 'studioespresso/craft-seo-fields',
        docsDir: 'docs',
        docsBranch: 'develop',
        editLinks: true,
        editLinkText: 'Help us improve this page!',
        sidebarDepth: 2,
        displayAllHeaders: true,
        sidebar: [
            '/',
            '/general',
            ['/field', 'Field & templating'],
            '/robots',
            '/sitemap',
        ],
        nav: [
            {
                text: 'Plugins',
                items: [
                    {text: 'SEO Fields', link: '/'}
                ]
            },
            {
                text: 'Questions? Get in touch!',
                link: 'mailto:info@studioespresso.co',
            }
        ]
    }
}