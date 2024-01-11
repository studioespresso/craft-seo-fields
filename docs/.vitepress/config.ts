module.exports = {
    title: 'SEO Fields',
    description: "SEO Fields for Craft CMS",
    base: '/craft-seo-fields',
    head: [
        ['meta', {content: 'https://github.com/studioespresso', property: 'og:see_also',}],
        [
            'script',
            {
                defer: '',
                'data-domain': 'studioespresso.github.io',
                src: 'https://stats.studioespresso.co/js/script.tagged-events.outbound-links.js'
            }
        ],
    ],
    themeConfig: {
        logo: '/img/plugin-logo.svg',
        sidebar: [
            {
                text: 'General',
                items:
                    [
                        {text: 'Usage', link: '/general'},
                        {text: 'Field & settings', link: '/field'},
                        {text: 'Templating', link: '/templating'},

                    ]
            },
            {
                text: 'Features',
                items:
                    [
                        {text: 'Robots.txt', link: '/robots'},
                        {text: 'Sitemap.xml', link: '/sitemap'},
                        {text: 'Redirects', link: '/redirects'},
                        {text: '404 tracking', link: '/notfound'},
                        {text: 'Schema.org markup', link: '/schema'},
                    ]
            }
        ],
        nav: [
            {
                text: 'Buy now',
                link: 'https://plugins.craftcms.com/seo-fields',
            },
            {
                text: 'Report an issue',
                link: 'https://github.com/studioespresso/craft-seo-fields/issues'
            },
            {
                text: 'GitHub',
                link: 'https://github.com/studioespresso/craft-seo-fields'
            }
        ]

    }
}
;