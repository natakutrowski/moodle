const webpack = require('webpack');
const path = require('path');

module.exports = {
    target: ['web', 'es2021'],
    optimization: {
        minimize: false,
    },
    entry: {
        'confetti': './ts/src/confetti.ts',
        'react-missions': './ts/src/missions.tsx',
        'react-resource-creator': './ts/src/resource-creator.tsx',
    },
    output: {
        filename: '[name]-lazy.js',
        path: path.resolve(__dirname, './amd/src'),
        libraryTarget: 'amd',
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
        ],
    },
    resolve: {
        extensions: ['.tsx', '.ts', '.js'],
    },
    plugins: [
        // Without this, Moodle prevents grunt from compiling the file.
        new webpack.BannerPlugin({
            banner: '/* eslint-disable */\n/* Do not edit directly, refer to ts/ folder. */',
            raw: true,
            entryOnly: true,
        }),
    ],
};
