/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./ts/frontend/GiftCardBalanceChecker.tsx":
/*!************************************************!*\
  !*** ./ts/frontend/GiftCardBalanceChecker.tsx ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   GiftCardBalanceChecker: () => (/* binding */ GiftCardBalanceChecker),\n/* harmony export */   GiftCardCouponInput: () => (/* binding */ GiftCardCouponInput),\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__),\n/* harmony export */   initGiftCardIntegration: () => (/* binding */ initGiftCardIntegration)\n/* harmony export */ });\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ \"react\");\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/plugins */ \"@wordpress/plugins\");\n/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_2__);\nvar __awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {\n    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }\n    return new (P || (P = Promise))(function (resolve, reject) {\n        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }\n        function rejected(value) { try { step(generator[\"throw\"](value)); } catch (e) { reject(e); } }\n        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }\n        step((generator = generator.apply(thisArg, _arguments || [])).next());\n    });\n};\n\n\n\n// Status enum for the balance checking process\nvar CheckStatus;\n(function (CheckStatus) {\n    CheckStatus[\"IDLE\"] = \"idle\";\n    CheckStatus[\"CHECKING\"] = \"checking\";\n    CheckStatus[\"SUCCESS\"] = \"success\";\n    CheckStatus[\"ERROR\"] = \"error\";\n})(CheckStatus || (CheckStatus = {}));\n// Function to check gift card balance (same as original)\nfunction checkGiftcardBalance(couponCode) {\n    return __awaiter(this, void 0, void 0, function* () {\n        try {\n            const formData = new FormData();\n            formData.append(\"action\", \"leat_check_giftcard_balance\");\n            formData.append(\"coupon_code\", couponCode);\n            formData.append(\"nonce\", window.leatGiftCardConfig.nonce);\n            const response = yield fetch(window.leatGiftCardConfig.ajaxUrl, {\n                method: \"POST\",\n                body: formData,\n            });\n            const data = yield response.json();\n            return data;\n        }\n        catch (error) {\n            console.error(\"Error checking gift card balance\", error);\n            return { success: false };\n        }\n    });\n}\n// Gift Card Balance Checker Component\nconst GiftCardBalanceChecker = ({ couponCode, cart, }) => {\n    const [status, setStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(CheckStatus.IDLE);\n    const [balance, setBalance] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(\"\");\n    const [giftCardBalances, setGiftCardBalances] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({});\n    // Function to check the balance\n    const checkBalance = (code) => __awaiter(void 0, void 0, void 0, function* () {\n        var _a, _b;\n        if (!code || code.length < 9) {\n            return;\n        }\n        setStatus(CheckStatus.CHECKING);\n        const response = yield checkGiftcardBalance(code);\n        if (response.success && ((_a = response.data) === null || _a === void 0 ? void 0 : _a.is_giftcard)) {\n            const balanceValue = ((_b = response.data) === null || _b === void 0 ? void 0 : _b.balance) || \"\";\n            if (balanceValue) {\n                setBalance(balanceValue);\n                // Add to balances collection\n                setGiftCardBalances((prev) => (Object.assign(Object.assign({}, prev), { [code]: balanceValue })));\n            }\n            setStatus(CheckStatus.SUCCESS);\n        }\n        else {\n            setStatus(CheckStatus.ERROR);\n        }\n    });\n    // Check balance when the component mounts or when coupon code changes\n    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {\n        console.log(\"couponCode\", couponCode);\n        if (couponCode && couponCode.length >= 9) {\n            const timer = setTimeout(() => {\n                checkBalance(couponCode);\n            }, 500);\n            return () => clearTimeout(timer);\n        }\n        else {\n            setStatus(CheckStatus.IDLE);\n        }\n    }, [couponCode]);\n    // Check all coupons in the cart\n    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {\n        var _a;\n        if (!((_a = cart === null || cart === void 0 ? void 0 : cart.cartCoupons) === null || _a === void 0 ? void 0 : _a.length))\n            return;\n        const checkCoupons = () => __awaiter(void 0, void 0, void 0, function* () {\n            if (!cart.cartCoupons)\n                return;\n            for (const coupon of cart.cartCoupons) {\n                if (coupon.code && coupon.code.length >= 9) {\n                    yield checkBalance(coupon.code);\n                }\n            }\n        });\n        checkCoupons();\n    }, [cart === null || cart === void 0 ? void 0 : cart.cartCoupons]);\n    // Don't render anything if there's no valid gift card applied\n    if (Object.keys(giftCardBalances).length === 0 && status === CheckStatus.IDLE) {\n        return null;\n    }\n    // Render balance information for a single coupon being checked\n    if (couponCode && status !== CheckStatus.IDLE) {\n        return (react__WEBPACK_IMPORTED_MODULE_0___default().createElement(\"div\", { className: `leat-giftcard-balance ${status === CheckStatus.SUCCESS ? \"success\" : \"\"}` },\n            status === CheckStatus.CHECKING && window.leatGiftCardConfig.checkingText,\n            status === CheckStatus.SUCCESS && (react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null,\n                window.leatGiftCardConfig.balanceText,\n                \" \",\n                react__WEBPACK_IMPORTED_MODULE_0___default().createElement(\"strong\", null, balance)))));\n    }\n    // Render balances for all gift cards in the cart\n    return (react__WEBPACK_IMPORTED_MODULE_0___default().createElement(\"div\", { className: \"leat-giftcard-balances\" }, Object.entries(giftCardBalances).map(([code, balance]) => (react__WEBPACK_IMPORTED_MODULE_0___default().createElement(\"div\", { key: code, className: \"leat-giftcard-balance success\" },\n        react__WEBPACK_IMPORTED_MODULE_0___default().createElement(\"strong\", null,\n            code,\n            \":\"),\n        \" \",\n        window.leatGiftCardConfig.balanceText,\n        \" \",\n        react__WEBPACK_IMPORTED_MODULE_0___default().createElement(\"span\", { dangerouslySetInnerHTML: { __html: balance } }))))));\n};\n// React component for coupon input that includes balance checking\nconst GiftCardCouponInput = () => {\n    const [couponCode, setCouponCode] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(\"\");\n    const handleInputChange = (e) => {\n        setCouponCode(e.target.value.trim());\n    };\n    return (react__WEBPACK_IMPORTED_MODULE_0___default().createElement(\"div\", { className: \"gift-card-coupon-input-container\" },\n        react__WEBPACK_IMPORTED_MODULE_0___default().createElement(\"input\", { type: \"text\", className: \"wc-block-components-totals-coupon__input\", value: couponCode, onChange: handleInputChange, placeholder: \"Enter gift card code\" }),\n        react__WEBPACK_IMPORTED_MODULE_0___default().createElement(GiftCardBalanceChecker, { couponCode: couponCode })));\n};\n// Initialize WooCommerce Blocks integration\nfunction initGiftCardIntegration() {\n    // Try to get the appropriate component (OrderMeta is preferred, DiscountsMeta as fallback)\n    const { ExperimentalOrderMeta, ExperimentalDiscountsMeta } = window.wc.blocksCheckout;\n    // Determine which component to use\n    const SlotComponent = ExperimentalOrderMeta || ExperimentalDiscountsMeta;\n    if (!SlotComponent) {\n        console.error(\"WooCommerce Blocks checkout components not found\");\n        return;\n    }\n    const render = () => {\n        return (react__WEBPACK_IMPORTED_MODULE_0___default().createElement(SlotComponent, null,\n            react__WEBPACK_IMPORTED_MODULE_0___default().createElement(GiftCardBalanceChecker, null)));\n    };\n    // Register the plugin for both cart and checkout contexts\n    (0,_wordpress_plugins__WEBPACK_IMPORTED_MODULE_2__.registerPlugin)(\"leat-giftcard-balance-checker\", {\n        render,\n        scope: \"woocommerce-checkout\",\n    });\n    (0,_wordpress_plugins__WEBPACK_IMPORTED_MODULE_2__.registerPlugin)(\"leat-giftcard-balance-checker-cart\", {\n        render,\n        scope: \"woocommerce-cart\",\n    });\n}\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (GiftCardBalanceChecker);\n\n\n//# sourceURL=webpack://@leat/plugin/./ts/frontend/GiftCardBalanceChecker.tsx?");

/***/ }),

/***/ "./ts/frontend/giftcard-checkout-integration.ts":
/*!******************************************************!*\
  !*** ./ts/frontend/giftcard-checkout-integration.ts ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _GiftCardBalanceChecker__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./GiftCardBalanceChecker */ \"./ts/frontend/GiftCardBalanceChecker.tsx\");\n/**\n * Leat Gift Card Integration for WooCommerce Blocks\n *\n * This script adds gift card balance display to the WooCommerce checkout.\n */\n\n// Initialize the integration when the DOM is ready\ndocument.addEventListener(\"DOMContentLoaded\", () => {\n    try {\n        // Verify required dependencies are available\n        if (typeof window.wp === \"undefined\" ||\n            typeof window.wc === \"undefined\" ||\n            typeof window.wc.blocksCheckout === \"undefined\" ||\n            (typeof window.wc.blocksCheckout.ExperimentalOrderMeta === \"undefined\" &&\n                typeof window.wc.blocksCheckout.ExperimentalDiscountsMeta === \"undefined\") ||\n            typeof window.wp.plugins === \"undefined\") {\n            console.error(\"Required WordPress or WooCommerce Blocks components not found\");\n            return;\n        }\n        // Check if leatGiftCardConfig is available\n        if (typeof window.leatGiftCardConfig === \"undefined\") {\n            console.error(\"Gift card configuration not found\");\n            return;\n        }\n        // Initialize the gift card balance checker component\n        (0,_GiftCardBalanceChecker__WEBPACK_IMPORTED_MODULE_0__.initGiftCardIntegration)();\n        console.log(\"Gift card integration initialized successfully\");\n    }\n    catch (error) {\n        console.error(\"Error initializing gift card integration:\", error);\n    }\n});\n\n\n//# sourceURL=webpack://@leat/plugin/./ts/frontend/giftcard-checkout-integration.ts?");

/***/ }),

/***/ "@wordpress/i18n":
/*!**************************!*\
  !*** external "wp.i18n" ***!
  \**************************/
/***/ ((module) => {

module.exports = wp.i18n;

/***/ }),

/***/ "@wordpress/plugins":
/*!*****************************!*\
  !*** external "wp.plugins" ***!
  \*****************************/
/***/ ((module) => {

module.exports = wp.plugins;

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = React;

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./ts/frontend/giftcard-checkout-integration.ts");
/******/ 	
/******/ })()
;