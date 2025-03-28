import path from "node:path";
import process from "node:process";
import TerserPlugin from "terser-webpack-plugin";
import MiniCssExtractPlugin from "mini-css-extract-plugin";
import DependenciesPlugin from "@wordpress/dependency-extraction-webpack-plugin";
const __dirname = path.resolve();

export default {
	mode: process.env?.NODE_ENV || "development",
	entry: {
		"giftcard-checkout-integration":
			"./ts/frontend/blocks/giftcard-balance-checker/giftcard-checkout-integration.ts",
		"gift-card-styles":
			"./ts/frontend/blocks/giftcard-balance-checker/giftcard-balance-checker.scss",
	},
	output: {
		path: path.resolve(__dirname, "dist/frontend/blocks"),
		filename: (pathData) => {
			return pathData.chunk.name === "gift-card-styles"
				? "[name].[contenthash].css"
				: "[name].[contenthash].js";
		},
		assetModuleFilename: "[name].[contenthash][ext]",
		clean: true,
	},
	resolve: {
		extensions: [".ts", ".tsx", ".js", ".jsx", ".scss"],
	},
	module: {
		rules: [
			{
				test: /\.tsx?$/,
				use: "ts-loader",
				exclude: /node_modules/,
			},
			{
				test: /\.jsx?$/,
				use: "babel-loader",
				exclude: /node_modules/,
			},
			{
				test: /\.scss$/,
				use: [
					MiniCssExtractPlugin.loader,
					"css-loader",
					"postcss-loader",
					{
						loader: "sass-loader",
						options: {
							sassOptions: {
								outputStyle: "compressed",
							},
						},
					},
				],
			},
		],
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: "[name].[contenthash].css",
		}),
		new DependenciesPlugin({
			outputFilename: "[name].asset.php",
			combineAssets: true,
			// requestToExternal: (request) => {
			// 	const externals = {
			// 		react: "React",
			// 		"react-dom": "ReactDOM",
			// 		"@wordpress/element": "wp.element",
			// 		"@wordpress/i18n": "wp.i18n",
			// 		"@wordpress/components": "wp.components",
			// 		"@wordpress/data": "wp.data",
			// 		"@wordpress/hooks": "wp.hooks",
			// 		"@wordpress/plugins": "wp.plugins",
			// 		"@woocommerce/blocks-checkout": "wc.blocksCheckout",
			// 		jquery: "jQuery",
			// 	};
			// 	return externals[request];
			// },
			// requestToHandle: (request) => {
			// 	const handles = {
			// 		react: "react",
			// 		"react-dom": "react-dom",
			// 		"@wordpress/element": "wp-element",
			// 		"@wordpress/i18n": "wp-i18n",
			// 		"@wordpress/components": "wp-components",
			// 		"@wordpress/data": "wp-data",
			// 		"@wordpress/hooks": "wp-hooks",
			// 		"@wordpress/plugins": "wp-plugins",
			// 		"@woocommerce/blocks-checkout": "wc-blocks-checkout",
			// 		jquery: "jquery",
			// 	};
			// 	return handles[request];
			// },
		}),
	],
	optimization: {
		minimizer: [
			new TerserPlugin({
				terserOptions: {
					format: {
						comments: false,
					},
				},
				extractComments: false,
			}),
		],
	},
	externals: {
		react: "React",
		"react-dom": "ReactDOM",
		"@wordpress/element": "wp.element",
		"@wordpress/i18n": "wp.i18n",
		"@wordpress/components": "wp.components",
		"@wordpress/data": "wp.data",
		"@wordpress/hooks": "wp.hooks",
		"@wordpress/plugins": "wp.plugins",
		"@woocommerce/blocks-checkout": "wc.blocksCheckout",
		jquery: "jQuery",
	},
};
