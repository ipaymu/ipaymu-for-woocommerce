(function () {
	if (
		!window.wc ||
		!window.wc.wcSettings ||
		!window.wc.wcBlocksRegistry ||
		!window.wp ||
		!window.wp.element ||
		!window.wp.i18n ||
		!window.wp.htmlEntities
	) {
		return;
	}

	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { getSetting } = window.wc.wcSettings;
	const { createElement } = window.wp.element;
	const { __ } = window.wp.i18n;
	const { decodeEntities } = window.wp.htmlEntities;

	const settings = getSetting("ipaymu_data", {});

	const title =
		decodeEntities(settings.title || "") ||
		__("iPaymu Payment", "ipaymu-for-woocommerce");

	const description =
		settings.description ||
		__(
			"Pembayaran melalui Virtual Account (VA), QRIS, Alfamart/Indomaret, Direct Debit, Kartu Kredit, dan COD.",
			"ipaymu-for-woocommerce"
		);

	const Content = () =>
		createElement("p", null, decodeEntities(description));

	const Block_Gateway = {
		name: "ipaymu",
		label: title,
		content: createElement(Content),
		edit: createElement(Content),
		canMakePayment: () => true,
		ariaLabel: title,
		supports: {
			features: settings.supports || {},
		},
	};

	registerPaymentMethod(Block_Gateway);
})();
