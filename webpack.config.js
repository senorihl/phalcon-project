const {WebpackManifestPlugin} = require('webpack-manifest-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const {resolve} = require('path');

module.exports = ({production = false}) => {
    return {
        mode: production ? "production" : "development",
        context: resolve(__dirname, "assets"),
        output: {
            filename: production ? '[contenthash].js' : '[name].[contenthash].js',
            path: resolve(__dirname, 'public/assets'),
            publicPath: '/assets',
            clean: true,
        },
        module: {
            rules: [
                {
                    test: /\.css$/i,
                    use: [
                        MiniCssExtractPlugin.loader,
                        "css-loader",
                        {
                            loader: "postcss-loader",
                            options: {
                                postcssOptions: {
                                    plugins: [
                                        [
                                            "postcss-preset-env",
                                            {
                                                // Options
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
        entry: [
            './app.js',
            './app.css'
        ],
        plugins: [
            new WebpackManifestPlugin(),
            new MiniCssExtractPlugin({
                filename: production ? '[contenthash].css' : '[name].[contenthash].css',
            })
        ]
    }
};