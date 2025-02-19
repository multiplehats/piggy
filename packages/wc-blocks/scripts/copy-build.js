const path = require("node:path");
const fs = require("fs-extra");

const blocks = [
	{
		name: "gift-card-recipient",
		source: path.resolve(__dirname, "../src/gift-card-recipient/build"),
		target: path.resolve(
			__dirname,
			"../../../apps/plugin/src/Blocks/GiftcardRecipientBlock/build"
		),
	},
];

// Copy build files for each block
blocks.forEach(({ name, source, target }) => {
	// Ensure target directory exists
	fs.ensureDirSync(target);

	// Copy build files
	fs.copySync(source, target, {
		overwrite: true,
	});

	console.log(`Build files for ${name} copied successfully!`);
});
