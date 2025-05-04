/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.html", // Root එකේ තියෙන HTML files
    "./**/*.php" // පස්සේ PHP files දැම්මොත් (ඕනෑම subfolder එකක)
    // ඔයාගේ structure එක අනුව path එක වෙනස් කරන්න
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}