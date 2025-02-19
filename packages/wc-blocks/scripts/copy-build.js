const path = require("node:path");
const fs = require("fs-extra");

const blocks = [
	{
		name: "gift-card-recipient",
		source: path.resolve(__dirname, "../build/gift-card-recipient"),
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

	console.info(`Build files for ${name} copied successfully!`);
});
