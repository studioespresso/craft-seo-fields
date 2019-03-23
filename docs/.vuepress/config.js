module.exports = {
    base: '/plugins/seo-fields/',
    title: 'Studio Espresso',
    description: '',
    themeConfig: {
        logo: '/icon-vuepress.svg',
        search: true,
        searchMaxSuggestions: 5,
        repo: 'vuejs/vuepress',
        repoLabel: 'Contribute!',
        docsRepo: 'studioespresso/craft-seo-fields',
        docsDir: 'docs',
        docsBranch: 'develop',
        editLinks: true,
        editLinkText: 'Help us improve this page!',
        nav: [
            {
                text: 'Plugins',
                items: [
                    { text: 'SEO Fields', link: '/' }
                ]
            }
        ]
    }
}