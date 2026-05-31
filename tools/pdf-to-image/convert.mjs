import { promises as fs } from 'fs';
import { pdf } from 'pdf-to-img';

const input = process.argv[2];
const output = process.argv[3];

if (!input || !output) {
  console.error('Uso: node convert.mjs <entrada.pdf> <salida.png>');
  process.exit(1);
}

try {
  const document = await pdf(input, { scale: 2 });
  let saved = false;

  for await (const image of document) {
    await fs.writeFile(output, image);
    saved = true;
    break;
  }

  if (!saved) {
    console.error('El PDF no tiene páginas.');
    process.exit(2);
  }
} catch (error) {
  console.error(error.message || error);
  process.exit(3);
}
