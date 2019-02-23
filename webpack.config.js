const path = require("path");

module.exports = {
    entry: {

        robots_codemirror: "./src/assetbundles/src/js/robots-codemirror.js",
    },
    output: {
        filename: "[name].min.js",
        path: path.join(__dirname, "/src/assetbundles/dist/js")
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: ['css-loader'],
            },
        ],
    }
}