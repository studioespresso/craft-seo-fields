import {defineConfig} from 'vitepress'
import DefaultTheme from 'vitepress/theme'



export default defineConfig({
    title: 'SEO Fields for Craft',
    base: '/craft-seo-fields',
    themeConfig: {
        logo: '/icon-vuepress.svg',
        sidebar: [
            {
                items: [
                    {text: 'General', link: '/general'},
                    {text: 'Field & settings', link: '/field'},
                    {text: 'Templating', link: '/templating'},
                    {text: 'Robots.txt', link: '/robots'},
                    {text: 'Sitemap.xml', link: '/sitemap'},
                    {text: 'Extra', link: '/extra'},
                ]
            },


        ],
        nav: [
            {
                text: 'Buy now',
                link: 'https://plugins.craftcms.com/seo-fields',
            },
            {
                text: 'Issues?',
                link: 'https://github.com/studioespresso/craft-seo-fields/issues'
            }
        ]

    }
})