import path from "node:path";
import process from "node:process";
import TerserPlugin from "terser-webpack-plugin";
import MiniCssExtractPlugin from "mini-css-extract-plugin";
const __dirname = path.resolve();

export default {
	mode: process.env?.NODE_ENV || "development",
	entry: {
		"giftcard-checkout-integration": "./ts/frontend/giftcard-checkout-integration.ts",
		"gift-card-styles": "./scss/gift-card-balance.scss",
	},
	output: {
		path: path.resolve(__dirname, "assets/js/frontend"),
		filename: "[name].js",
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
		],
	},
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
