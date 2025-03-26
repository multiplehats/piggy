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

/***/ "../../packages/lib/index.ts":
/*!***********************************!*\
  !*** ../../packages/lib/index.ts ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   api: () => (/* reexport safe */ _src_api__WEBPACK_IMPORTED_MODULE_3__.api),\n/* harmony export */   clickOutsideAction: () => (/* reexport safe */ _svelte_actions__WEBPACK_IMPORTED_MODULE_2__.clickOutsideAction),\n/* harmony export */   handleEscapeAction: () => (/* reexport safe */ _svelte_actions__WEBPACK_IMPORTED_MODULE_2__.handleEscapeAction),\n/* harmony export */   outboundUrl: () => (/* reexport safe */ _outbound_url__WEBPACK_IMPORTED_MODULE_0__[\"default\"]),\n/* harmony export */   replaceStrings: () => (/* reexport safe */ _replace_strings__WEBPACK_IMPORTED_MODULE_1__.replaceStrings),\n/* harmony export */   trapFocusAction: () => (/* reexport safe */ _svelte_actions__WEBPACK_IMPORTED_MODULE_2__.trapFocusAction)\n/* harmony export */ });\n/* harmony import */ var _outbound_url__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./outbound-url */ \"../../packages/lib/outbound-url.ts\");\n/* harmony import */ var _replace_strings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./replace-strings */ \"../../packages/lib/replace-strings.ts\");\n/* harmony import */ var _svelte_actions__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./svelte-actions */ \"../../packages/lib/svelte-actions/index.ts\");\n/* harmony import */ var _src_api__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./src/api */ \"../../packages/lib/src/api.ts\");\n\n\n\n\n\n\n//# sourceURL=webpack://@leat/plugin/../../packages/lib/index.ts?");

/***/ }),

/***/ "../../packages/lib/outbound-url.ts":
/*!******************************************!*\
  !*** ../../packages/lib/outbound-url.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ outboundUrl)\n/* harmony export */ });\n/**\n * Create outbound URL with UTM params and custom params.\n */\nfunction outboundUrl({ url, source, campaign, medium, params, }) {\n    const outboundLink = new URL(url);\n    // Create object from opts\n    const urlParams = Object.assign({ utm_source: source, utm_medium: medium, utm_campaign: campaign }, params);\n    // Add params to URL\n    Object.keys(urlParams).forEach((key) => {\n        if (urlParams[key]) {\n            outboundLink.searchParams.append(key, urlParams[key]);\n        }\n    });\n    return outboundLink.toString();\n}\n\n\n//# sourceURL=webpack://@leat/plugin/../../packages/lib/outbound-url.ts?");

/***/ }),

/***/ "../../packages/lib/replace-strings.ts":
/*!*********************************************!*\
  !*** ../../packages/lib/replace-strings.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   replaceStrings: () => (/* binding */ replaceStrings)\n/* harmony export */ });\nfunction replaceStrings(text, obj) {\n    return obj.reduce((acc, replacementObj) => {\n        Object.entries(replacementObj).forEach(([key, value]) => {\n            const regex = new RegExp(`{{\\\\s*${key.slice(2, -2).trim()}\\\\s*}}`, \"g\");\n            acc = acc.replace(regex, value !== null && value !== void 0 ? value : \"\");\n        });\n        return acc;\n    }, text);\n}\n\n\n//# sourceURL=webpack://@leat/plugin/../../packages/lib/replace-strings.ts?");

/***/ }),

/***/ "../../packages/lib/src/api.ts":
/*!*************************************!*\
  !*** ../../packages/lib/src/api.ts ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   api: () => (/* binding */ api)\n/* harmony export */ });\nvar __awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {\n    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }\n    return new (P || (P = Promise))(function (resolve, reject) {\n        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }\n        function rejected(value) { try { step(generator[\"throw\"](value)); } catch (e) { reject(e); } }\n        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }\n        step((generator = generator.apply(thisArg, _arguments || [])).next());\n    });\n};\n// API Wrapper\nfunction request() {\n    return __awaiter(this, arguments, void 0, function* (method = \"GET\", path, data, options) {\n        var _a, _b, _c, _d;\n        try {\n            // Ensure we have the nonce\n            const nonce = (_a = window === null || window === void 0 ? void 0 : window.leatMiddlewareConfig) === null || _a === void 0 ? void 0 : _a.wpApiNonce;\n            if (!nonce) {\n                console.error(\"WordPress API nonce is missing\");\n            }\n            const response = yield fetch(path.startsWith(\"http\") ? path : `/wp-json${path}`, Object.assign(Object.assign({ method, headers: Object.assign(Object.assign({ Accept: \"application/json, */*;q=0.1\", \"Cache-Control\": \"no-cache\", Pragma: \"no-cache\", \"X-WP-Nonce\": nonce || \"\" }, (method !== \"GET\" && data ? { \"Content-Type\": \"application/json\" } : {})), ((options === null || options === void 0 ? void 0 : options.headers) || {})) }, (method !== \"GET\" && data ? { body: JSON.stringify(data) } : {})), { credentials: \"same-origin\", mode: \"same-origin\", referrerPolicy: \"strict-origin-when-cross-origin\" }));\n            if (!response.ok) {\n                const errorText = yield response.text();\n                let errorData;\n                try {\n                    errorData = JSON.parse(errorText);\n                }\n                catch (_e) {\n                    errorData = errorText;\n                }\n                return {\n                    data: null,\n                    error: {\n                        status: response.status,\n                        statusText: response.statusText,\n                        data: (errorData === null || errorData === void 0 ? void 0 : errorData.message) || errorText,\n                    },\n                };\n            }\n            const responseData = yield response.json();\n            // Handle WordPress error responses\n            if (responseData === null || responseData === void 0 ? void 0 : responseData.error_code) {\n                return {\n                    data: null,\n                    error: {\n                        status: 200,\n                        statusText: responseData.error_code,\n                        data: (_c = (_b = responseData === null || responseData === void 0 ? void 0 : responseData.additional_data) === null || _b === void 0 ? void 0 : _b.message) !== null && _c !== void 0 ? _c : \"Unknown error\",\n                    },\n                };\n            }\n            return {\n                data: responseData,\n                error: null,\n            };\n        }\n        catch (e) {\n            const err = e;\n            return {\n                data: null,\n                error: {\n                    status: ((_d = err === null || err === void 0 ? void 0 : err.data) === null || _d === void 0 ? void 0 : _d.status) ? err.data.status : 500,\n                    statusText: err ? err.code : \"unknown\",\n                    data: err ? err.message : \"unknown\",\n                },\n            };\n        }\n    });\n}\n/**\n * A simple fetch wrapper for making API requests\n */\nconst api = {\n    get: (path, options) => {\n        return request(\"GET\", path, undefined, options);\n    },\n    post: (path, data, options) => {\n        return request(\"POST\", path, data, options);\n    },\n    put: (path, data, options) => {\n        return request(\"PUT\", path, data, options);\n    },\n    delete: (path, options) => {\n        return request(\"DELETE\", path, undefined, options);\n    },\n};\n\n\n//# sourceURL=webpack://@leat/plugin/../../packages/lib/src/api.ts?");

/***/ }),

/***/ "../../packages/lib/svelte-actions/click-outside-action.ts":
/*!*****************************************************************!*\
  !*** ../../packages/lib/svelte-actions/click-outside-action.ts ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ clickOutside)\n/* harmony export */ });\nfunction clickOutside(node, params) {\n    let current_callback;\n    const handleClick = (event) => {\n        if (params.exclude &&\n            params.exclude.some((selector) => event.target.matches(selector))) {\n            return;\n        }\n        if (!node.contains(event.target)) {\n            current_callback();\n        }\n    };\n    const toggle = (current_params) => {\n        const { active, callback } = current_params;\n        if (active) {\n            current_callback = callback;\n            document.addEventListener(\"click\", handleClick, true);\n        }\n        else {\n            document.removeEventListener(\"click\", handleClick, true);\n        }\n    };\n    toggle(params);\n    return {\n        update(next_params) {\n            toggle(next_params);\n        },\n        destroy() {\n            toggle({ active: false, callback: current_callback });\n        },\n    };\n}\n\n\n//# sourceURL=webpack://@leat/plugin/../../packages/lib/svelte-actions/click-outside-action.ts?");

/***/ }),

/***/ "../../packages/lib/svelte-actions/escape-action.ts":
/*!**********************************************************!*\
  !*** ../../packages/lib/svelte-actions/escape-action.ts ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ handleEscape)\n/* harmony export */ });\nfunction handleEscape(node, params) {\n    let currentCallback;\n    const handleKeydown = (event) => {\n        if (event.code === \"Escape\") {\n            currentCallback();\n        }\n    };\n    const toggle = (active, current_params) => {\n        if (active) {\n            currentCallback = current_params.callback;\n            node.addEventListener(\"keydown\", handleKeydown, true);\n        }\n        else {\n            node.removeEventListener(\"keydown\", handleKeydown, true);\n        }\n    };\n    const destroy = () => toggle(false, { callback: currentCallback });\n    toggle(true, params);\n    return {\n        destroy,\n        update(next_params) {\n            destroy();\n            toggle(true, next_params);\n        },\n    };\n}\n\n\n//# sourceURL=webpack://@leat/plugin/../../packages/lib/svelte-actions/escape-action.ts?");

/***/ }),

/***/ "../../packages/lib/svelte-actions/index.ts":
/*!**************************************************!*\
  !*** ../../packages/lib/svelte-actions/index.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   clickOutsideAction: () => (/* reexport safe */ _click_outside_action__WEBPACK_IMPORTED_MODULE_0__[\"default\"]),\n/* harmony export */   handleEscapeAction: () => (/* reexport safe */ _escape_action__WEBPACK_IMPORTED_MODULE_1__[\"default\"]),\n/* harmony export */   trapFocusAction: () => (/* reexport safe */ _trap_focus_action__WEBPACK_IMPORTED_MODULE_2__[\"default\"])\n/* harmony export */ });\n/* harmony import */ var _click_outside_action__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./click-outside-action */ \"../../packages/lib/svelte-actions/click-outside-action.ts\");\n/* harmony import */ var _escape_action__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./escape-action */ \"../../packages/lib/svelte-actions/escape-action.ts\");\n/* harmony import */ var _trap_focus_action__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./trap-focus-action */ \"../../packages/lib/svelte-actions/trap-focus-action.ts\");\n\n\n\n\n\n//# sourceURL=webpack://@leat/plugin/../../packages/lib/svelte-actions/index.ts?");

/***/ }),

/***/ "../../packages/lib/svelte-actions/trap-focus-action.ts":
/*!**************************************************************!*\
  !*** ../../packages/lib/svelte-actions/trap-focus-action.ts ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ trapFocus)\n/* harmony export */ });\nfunction trapFocus(node) {\n    function handleKeydown(event) {\n        if (event.code !== \"Tab\") {\n            return;\n        }\n        event.preventDefault();\n        const tabbables = Array.from(node.querySelectorAll(\"*\")).filter((el) => {\n            return (\"tabIndex\" in el &&\n                el.tabIndex >= 0 &&\n                !el.hasAttribute(\"disabled\") &&\n                !el.hasAttribute(\"hidden\") &&\n                !el.getAttribute(\"aria-hidden\"));\n        });\n        if (!tabbables.length) {\n            return;\n        }\n        // Index of element that's currently in focus.\n        let index = tabbables.indexOf(node.ownerDocument.activeElement);\n        // The focus is outside. Reset it.\n        if (index === -1) {\n            index = 0;\n        }\n        index += tabbables.length + (event.shiftKey ? -1 : 1);\n        index %= tabbables.length;\n        // @ts-expect-error This is fine.\n        tabbables[index].focus();\n    }\n    function toggleListeners(shouldListen) {\n        if (shouldListen) {\n            node.addEventListener(\"keydown\", handleKeydown);\n        }\n        else {\n            node.removeEventListener(\"keydown\", handleKeydown);\n        }\n    }\n    toggleListeners(true);\n    return {\n        destroy() {\n            toggleListeners(false);\n        },\n    };\n}\n\n\n//# sourceURL=webpack://@leat/plugin/../../packages/lib/svelte-actions/trap-focus-action.ts?");

/***/ }),

/***/ "./ts/frontend/blocks/giftcard-balance-checker/GiftCardBalanceChecker.tsx":
/*!********************************************************************************!*\
  !*** ./ts/frontend/blocks/giftcard-balance-checker/GiftCardBalanceChecker.tsx ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   GiftCardBalanceChecker: () => (/* binding */ GiftCardBalanceChecker),\n/* harmony export */   GiftCardCouponInput: () => (/* binding */ GiftCardCouponInput),\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__),\n/* harmony export */   initGiftCardIntegration: () => (/* binding */ initGiftCardIntegration)\n/* harmony export */ });\n/* harmony import */ var _leat_lib__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @leat/lib */ \"../../packages/lib/index.ts\");\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ \"react\");\n/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/plugins */ \"@wordpress/plugins\");\n/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _giftcard_balance_checker_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./giftcard-balance-checker.scss */ \"./ts/frontend/blocks/giftcard-balance-checker/giftcard-balance-checker.scss\");\nvar __awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {\n    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }\n    return new (P || (P = Promise))(function (resolve, reject) {\n        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }\n        function rejected(value) { try { step(generator[\"throw\"](value)); } catch (e) { reject(e); } }\n        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }\n        step((generator = generator.apply(thisArg, _arguments || [])).next());\n    });\n};\n\n\n\n\n\nvar CheckStatus;\n(function (CheckStatus) {\n    CheckStatus[\"IDLE\"] = \"idle\";\n    CheckStatus[\"CHECKING\"] = \"checking\";\n    CheckStatus[\"SUCCESS\"] = \"success\";\n    CheckStatus[\"ERROR\"] = \"error\";\n})(CheckStatus || (CheckStatus = {}));\nfunction checkGiftcardBalance(couponCode) {\n    return __awaiter(this, void 0, void 0, function* () {\n        try {\n            const formData = new FormData();\n            formData.append(\"action\", \"leat_check_giftcard_balance\");\n            formData.append(\"coupon_code\", couponCode);\n            formData.append(\"nonce\", window.leatGiftCardConfig.nonce);\n            const response = yield fetch(window.leatGiftCardConfig.ajaxUrl, {\n                method: \"POST\",\n                body: formData,\n            });\n            const data = yield response.json();\n            return data;\n        }\n        catch (error) {\n            console.error(\"Error checking gift card balance\", error);\n            return { success: false };\n        }\n    });\n}\n// Gift Card Balance Checker Component\nconst GiftCardBalanceChecker = ({ couponCode, cart, }) => {\n    const [status, setStatus] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(CheckStatus.IDLE);\n    const [balance, setBalance] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(\"\");\n    const [giftCardBalances, setGiftCardBalances] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)({});\n    console.info(\"cart\", cart);\n    const checkBalance = (code) => __awaiter(void 0, void 0, void 0, function* () {\n        var _a, _b;\n        if (!code || code.length < 9) {\n            return;\n        }\n        setStatus(CheckStatus.CHECKING);\n        const response = yield checkGiftcardBalance(code);\n        if (response.success && ((_a = response.data) === null || _a === void 0 ? void 0 : _a.is_giftcard)) {\n            const balanceValue = ((_b = response.data) === null || _b === void 0 ? void 0 : _b.balance) || \"\";\n            if (balanceValue) {\n                setBalance(balanceValue);\n                // Add to balances collection\n                setGiftCardBalances((prev) => (Object.assign(Object.assign({}, prev), { [code]: balanceValue })));\n            }\n            setStatus(CheckStatus.SUCCESS);\n        }\n        else {\n            setStatus(CheckStatus.ERROR);\n        }\n    });\n    // Check balance when the component mounts or when coupon code changes\n    (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {\n        if (couponCode && couponCode.length >= 9) {\n            const timer = setTimeout(() => {\n                checkBalance(couponCode);\n            }, 500);\n            return () => clearTimeout(timer);\n        }\n        else {\n            setStatus(CheckStatus.IDLE);\n        }\n    }, [couponCode]);\n    // Check all coupons in the cart\n    (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {\n        var _a;\n        if (!((_a = cart === null || cart === void 0 ? void 0 : cart.cartCoupons) === null || _a === void 0 ? void 0 : _a.length)) {\n            // If cart is updated and has no coupons, clear the balances\n            if (Object.keys(giftCardBalances).length > 0) {\n                setGiftCardBalances({});\n                setBalance(\"\");\n                setStatus(CheckStatus.IDLE);\n            }\n            return;\n        }\n        const checkCoupons = () => __awaiter(void 0, void 0, void 0, function* () {\n            if (!cart.cartCoupons)\n                return;\n            // Create a new balances object to track current coupons\n            const newBalances = {};\n            let balancesChanged = false;\n            for (const coupon of cart.cartCoupons) {\n                if (coupon.code && coupon.code.length >= 9) {\n                    // If we already have a balance for this code, keep it\n                    if (giftCardBalances[coupon.code]) {\n                        newBalances[coupon.code] = giftCardBalances[coupon.code];\n                    }\n                    else {\n                        // Otherwise check the balance\n                        yield checkBalance(coupon.code);\n                        // The checkBalance function will update giftCardBalances directly\n                        balancesChanged = true;\n                    }\n                }\n            }\n            // Only update if we didn't already update through checkBalance\n            // and the available coupons have changed\n            if (!balancesChanged &&\n                Object.keys(newBalances).length !== Object.keys(giftCardBalances).length) {\n                setGiftCardBalances(newBalances);\n            }\n        });\n        checkCoupons();\n    }, [cart === null || cart === void 0 ? void 0 : cart.cartCoupons]);\n    // Listen for coupon removal events\n    (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {\n        const handleCouponRemoved = (_event) => {\n            // Don't reset all balances immediately\n            // We'll rely on the cart prop updates to reflect the current state\n            // This provides a more granular update than resetting everything\n            var _a;\n            // If we don't have cart data, we can check on next cart update\n            if (!((_a = cart === null || cart === void 0 ? void 0 : cart.cartCoupons) === null || _a === void 0 ? void 0 : _a.length)) {\n                // Only reset if we actually have balances to show and no coupons left\n                setGiftCardBalances({});\n                setBalance(\"\");\n                setStatus(CheckStatus.IDLE);\n            }\n        };\n        // Add event listener for coupon removal\n        document.addEventListener(\"wc-blocks_removed_from_cart\", handleCouponRemoved);\n        // Clean up\n        return () => {\n            document.removeEventListener(\"wc-blocks_removed_from_cart\", handleCouponRemoved);\n        };\n    }, [cart === null || cart === void 0 ? void 0 : cart.cartCoupons]);\n    // Add event listener for added_to_cart events to update balances\n    (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {\n        const handleAddedToCart = (_event) => {\n            // The cart prop will be updated after this event\n            // Just ensure we're in a state that allows showing balances\n            if (status === CheckStatus.ERROR) {\n                setStatus(CheckStatus.IDLE);\n            }\n        };\n        document.addEventListener(\"wc-blocks_added_to_cart\", handleAddedToCart);\n        return () => {\n            document.removeEventListener(\"wc-blocks_added_to_cart\", handleAddedToCart);\n        };\n    }, [status]);\n    // Don't render anything if there's no valid gift card applied\n    if (Object.keys(giftCardBalances).length === 0 && status === CheckStatus.IDLE) {\n        return null;\n    }\n    // Render balance information for a single coupon being checked\n    if (couponCode && status !== CheckStatus.IDLE) {\n        return (react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"div\", { className: `leat-giftcard-balance ${status.toLowerCase()}` },\n            status === CheckStatus.CHECKING && window.leatGiftCardConfig.checkingText,\n            status === CheckStatus.SUCCESS && (react__WEBPACK_IMPORTED_MODULE_1___default().createElement((react__WEBPACK_IMPORTED_MODULE_1___default().Fragment), null,\n                window.leatGiftCardConfig.balanceText,\n                \" \",\n                react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"strong\", null, balance)))));\n    }\n    // Render balances for all gift cards in the cart\n    return (react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"div\", { className: \"leat-giftcard-balances\" }, Object.entries(giftCardBalances).map(([code, balance]) => (react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"div\", { key: code, className: \"leat-giftcard-balance success\" },\n        react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"div\", { className: \"gift-card-code-container\" },\n            react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"span\", { className: \"gift-card-code\" }, code),\n            react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"span\", { className: \"gift-card-balance\", dangerouslySetInnerHTML: { __html: balance } })))))));\n};\n// React component for coupon input that includes balance checking\nconst GiftCardCouponInput = () => {\n    const [couponCode, setCouponCode] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(\"\");\n    const handleInputChange = (e) => {\n        setCouponCode(e.target.value.trim());\n    };\n    return (react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"div\", { className: \"gift-card-coupon-input-container\" },\n        react__WEBPACK_IMPORTED_MODULE_1___default().createElement(\"input\", { type: \"text\", className: \"wc-block-components-totals-coupon__input\", value: couponCode, onChange: handleInputChange, placeholder: \"Enter gift card code\" }),\n        react__WEBPACK_IMPORTED_MODULE_1___default().createElement(GiftCardBalanceChecker, { couponCode: couponCode })));\n};\n// Initialize WooCommerce Blocks integration\nfunction initGiftCardIntegration() {\n    // Try to get the appropriate component (OrderMeta is preferred, DiscountsMeta as fallback)\n    const { ExperimentalOrderMeta, ExperimentalDiscountsMeta } = window.wc.blocksCheckout;\n    // Determine which component to use\n    const SlotComponent = ExperimentalOrderMeta || ExperimentalDiscountsMeta;\n    if (!SlotComponent) {\n        console.error(\"WooCommerce Blocks checkout components not found\");\n        return;\n    }\n    const render = () => {\n        return (react__WEBPACK_IMPORTED_MODULE_1___default().createElement(SlotComponent, null,\n            react__WEBPACK_IMPORTED_MODULE_1___default().createElement(GiftCardBalanceChecker, null)));\n    };\n    // Register the plugin for both cart and checkout contexts\n    (0,_wordpress_plugins__WEBPACK_IMPORTED_MODULE_3__.registerPlugin)(\"leat-giftcard-balance-checker\", {\n        render,\n        scope: \"woocommerce-checkout\",\n    });\n    (0,_wordpress_plugins__WEBPACK_IMPORTED_MODULE_3__.registerPlugin)(\"leat-giftcard-balance-checker-cart\", {\n        render,\n        scope: \"woocommerce-cart\",\n    });\n}\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (GiftCardBalanceChecker);\n\n\n//# sourceURL=webpack://@leat/plugin/./ts/frontend/blocks/giftcard-balance-checker/GiftCardBalanceChecker.tsx?");

/***/ }),

/***/ "./ts/frontend/blocks/giftcard-balance-checker/giftcard-balance-checker.scss":
/*!***********************************************************************************!*\
  !*** ./ts/frontend/blocks/giftcard-balance-checker/giftcard-balance-checker.scss ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n// extracted by mini-css-extract-plugin\n\n\n//# sourceURL=webpack://@leat/plugin/./ts/frontend/blocks/giftcard-balance-checker/giftcard-balance-checker.scss?");

/***/ }),

/***/ "./ts/frontend/blocks/giftcard-balance-checker/giftcard-checkout-integration.ts":
/*!**************************************************************************************!*\
  !*** ./ts/frontend/blocks/giftcard-balance-checker/giftcard-checkout-integration.ts ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _GiftCardBalanceChecker__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./GiftCardBalanceChecker */ \"./ts/frontend/blocks/giftcard-balance-checker/GiftCardBalanceChecker.tsx\");\n/**\n * Leat Gift Card Integration for WooCommerce Blocks\n *\n * This script adds gift card balance display to the WooCommerce checkout.\n */\n\n// Initialize the integration when the DOM is ready\ndocument.addEventListener(\"DOMContentLoaded\", () => {\n    try {\n        // Verify required dependencies are available\n        if (typeof window.wp === \"undefined\" ||\n            typeof window.wc === \"undefined\" ||\n            typeof window.wc.blocksCheckout === \"undefined\" ||\n            (typeof window.wc.blocksCheckout.ExperimentalOrderMeta === \"undefined\" &&\n                typeof window.wc.blocksCheckout.ExperimentalDiscountsMeta === \"undefined\") ||\n            typeof window.wp.plugins === \"undefined\") {\n            console.error(\"Required WordPress or WooCommerce Blocks components not found\");\n            return;\n        }\n        // Check if leatGiftCardConfig is available\n        if (typeof window.leatGiftCardConfig === \"undefined\") {\n            console.error(\"Gift card configuration not found\");\n            return;\n        }\n        (0,_GiftCardBalanceChecker__WEBPACK_IMPORTED_MODULE_0__.initGiftCardIntegration)();\n    }\n    catch (error) {\n        console.error(\"Error initializing gift card integration:\", error);\n    }\n});\n\n\n//# sourceURL=webpack://@leat/plugin/./ts/frontend/blocks/giftcard-balance-checker/giftcard-checkout-integration.ts?");

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
/******/ 	var __webpack_exports__ = __webpack_require__("./ts/frontend/blocks/giftcard-balance-checker/giftcard-checkout-integration.ts");
/******/ 	
/******/ })()
;