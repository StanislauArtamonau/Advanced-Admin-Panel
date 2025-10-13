const path = require('path')
const MiniCssExtractPlugin = require("mini-css-extract-plugin")


module.exports = (env, argv) => {

    const isDev = argv.mode === 'development';

    const config = {
        watchOptions: {
            ignored: /node_modules/
        },
        devtool: isDev ? 'inline-source-map' : false,
        entry: {
            'main': './adv-adm-pan/src/js/index.js',
        },
        output: {
            path: path.resolve(__dirname, 'adv-adm-pan/assets'),
            filename: 'js/[name].js',
            // clean: true,
        },
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: [
                        {
                            loader: "babel-loader",
                            options: {
                                presets: ["@babel/preset-env"],
                            },
                        },
                    ],
                },
                {
                    test: /\.(sa|sc|c)ss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: "css-loader",
                            options: {
                                url: false // Do not resolve url() in css or scss
                            },
                        },
                        'sass-loader',
                        {
                            loader: "postcss-loader",
                            options: {
                                postcssOptions: {
                                    plugins: [
                                        [
                                            "autoprefixer",
                                            {
                                                overrideBrowserslist: ["> 0.5%"],
                                            },
                                        ],
                                    ],
                                },
                            },
                        },
                    ],
                },
            ],
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: 'css/[name].css',
            })
        ],
    };

    return config;
}