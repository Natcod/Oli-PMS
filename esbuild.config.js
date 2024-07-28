// esbuild.config.js
const esbuild = require('esbuild');

esbuild.build({
  entryPoints: ['resources/js/app.js'],
  bundle: true,
  outfile: 'public/js/app.js',
  loader: {
    '.js': 'jsx',
  },
}).catch(() => process.exit(1));
