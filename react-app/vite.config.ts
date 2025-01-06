import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

console.log("Building with Vite...");

export default defineConfig({
  plugins: [react()],
  css: {
    postcss: "./postcss.config.js", // Ensure PostCSS configuration is used
  },
  build: {
    outDir: path.resolve(__dirname, "../dist"), // Output directory for the plugin
    emptyOutDir: true, // Clean the output directory before building
    manifest: true, // Enable manifest generation
    rollupOptions: {
      input: "./index.html", // Specify the entry point
    },
  },
  base: "./", // Ensure relative paths for assets
});
