const fs = require("fs");
const path = require("path");

// Regenera public/code/dist/css/adminlte.purged.min.css a partir de
// adminlte.min.css usando la configuracion de purge.config.cjs.
//
// USO:
//   cd tools/purgecss
//   npm install purgecss
//   node run-purge.cjs
//
// El CLI de purgecss esta roto en Windows (carga de config ESM) y la API v8
// ignora la opcion 'output', por eso los resultados se escriben manualmente.

let PurgeCSS;
try {
  ({ PurgeCSS } = require("purgecss"));
} catch (e) {
  console.error("Falta la dependencia 'purgecss'. Ejecuta primero:  npm install purgecss");
  process.exit(1);
}

const configPath = process.argv[2] || path.join(__dirname, "purge.config.cjs");
const config = require(path.resolve(configPath));

const TARGET = path.resolve(
  __dirname, "..", "..",
  "public", "code", "dist", "css", "adminlte.purged.min.css"
);

(async () => {
  const results = await new PurgeCSS().purge({
    content: config.content,
    css: config.css,
    safelist: config.safelist || {},
    keyframes: config.keyframes ?? false,
    fontFace: config.fontFace ?? false,
  });

  fs.mkdirSync(config.output, { recursive: true });

  const produced = path.join(config.output, path.basename(config.css[0]));
  for (const r of results) {
    const name = r.file ? path.basename(r.file) : "adminlte.min.css";
    fs.writeFileSync(path.join(config.output, name), r.css);
  }

  if (!fs.existsSync(produced)) {
    console.error("No se genero el archivo purgado en: " + produced);
    process.exit(1);
  }

  const origBytes = fs.statSync(config.css[0]).size;
  const purgedBytes = fs.statSync(produced).size;
  fs.copyFileSync(produced, TARGET);

  const pct = (100 - (purgedBytes / origBytes) * 100).toFixed(1);
  console.log("OK -> " + TARGET);
  console.log(
    "original: " + (origBytes / 1024).toFixed(1) + " KB | purgado: " +
    (purgedBytes / 1024).toFixed(1) + " KB (-" + pct + "%)"
  );
})().catch((e) => {
  console.error("ERROR:", e.message);
  process.exit(1);
});
