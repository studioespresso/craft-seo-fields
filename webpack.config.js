const path = require("path");
const ExtractTextPlugin = require("extract-text-webpack-plugin");

var ExtractCSS = new ExtractTextPlugin('[name]');

module.exports = {
    mode: 'production',
    entry: {
        'robots.min.js': "./src/assetbundles/src/js/robots_codemirror.js",
        'robots.css': "./src/assetbundles/src/css/robots.css",
    },
    output: {
        filename: "[name]",
        path: path.join(__dirname, "/src/assetbundles/dist")
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: ExtractCSS.extract({
                    fallback: "style-loader",
                    use: [
                        "css-loader",
                    ]
                })
            },
        ],
    },
    plugins: [
        ExtractCSS
    ],
    watch: true
}