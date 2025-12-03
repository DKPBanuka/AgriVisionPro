# AgriVisionPro JavaScript Version

This is a JavaScript-based conversion of the original HTML file for the AgriVisionPro agricultural guidance system. The application has been modularized into separate JavaScript files for better organization and maintainability.

## File Structure

```
AgriVisionPro-1/
├── index-js.html          # Main HTML file that loads the JS application
├── test-js.html           # Test file to verify module loading
├── js/
│   ├── app.js            # Main application class and logic
│   ├── data.js           # All agricultural data (crops, livestock, market)
│   └── charts.js         # Chart creation and management utilities
├── images/               # Image assets
└── [other original files]
```

## Features

### Modular Architecture
- **app.js**: Main application controller, handles UI generation and user interactions
- **data.js**: Centralized data management for crops, livestock, market prices, and resources
- **charts.js**: Chart utilities for creating various types of charts using Chart.js and ApexCharts

### Interactive Elements
- Dynamic content switching between crops and livestock
- Accordion-style information panels
- Interactive charts and data visualizations
- Custom mouse cursor with trail effects
- Smooth scrolling and animations
- Responsive design for mobile and desktop

### Agricultural Content
- **Crops**: Rice, Maize, Green Gram, Chili, Onion, Tomato, Banana, Mango, Cabbage
- **Livestock**: Cattle, Poultry, Buffaloes, Sheep, Pigs, Goats, and other small animals
- **Market Data**: Current prices and 30-day trend charts
- **Resources**: Government institutions, financial support, publications

## How to Use

### Development
1. Open `index-js.html` in a web browser
2. The application will automatically load and initialize all modules
3. Use `test-js.html` to verify that all modules are loading correctly

### Testing
- Open `test-js.html` to run module loading tests
- Check browser console for any JavaScript errors
- Verify that Chart.js and ApexCharts libraries are loading properly

## Technical Details

### Dependencies
- **Chart.js**: For timeline and water requirement charts
- **ApexCharts**: For market price trend charts
- **Font Awesome**: For icons
- **Tailwind CSS**: For styling (loaded via CDN)

### Browser Compatibility
- Modern browsers supporting ES6 classes
- Chart.js and ApexCharts compatibility
- Responsive design for mobile devices

### Performance Optimizations
- Modular loading of JavaScript files
- Chart instance management to prevent memory leaks
- Lazy initialization of charts
- Efficient DOM manipulation

## Key Classes

### AgriVisionPro
Main application class that:
- Initializes the application
- Manages UI state and navigation
- Handles user interactions
- Coordinates between modules

### AgriData
Static data class providing:
- Crop cultivation information
- Livestock management data  
- Market prices and trends
- Resource links and FAQs

### ChartUtils
Chart management class offering:
- Crop timeline and water charts
- Livestock performance charts
- Market trend visualizations
- Chart lifecycle management

## Customization

### Adding New Crops
1. Add crop data to `AgriData.getCropData()` in `data.js`
2. Include timeline and water requirement data
3. Add cultivation steps and requirements

### Adding New Livestock
1. Add livestock data to `AgriData.getLivestockData()` in `data.js`
2. Define chart data structure
3. Include management steps and requirements

### Modifying Charts
1. Update chart configurations in `charts.js`
2. Add new chart types as needed
3. Customize colors and styling

## Benefits of JavaScript Version

1. **Modularity**: Better code organization and maintainability
2. **Reusability**: Components can be reused across different pages
3. **Performance**: Only load data when needed
4. **Extensibility**: Easy to add new features and content
5. **Testing**: Separate test file for verification
6. **Debugging**: Cleaner code structure for easier debugging

## Future Enhancements

- Add data persistence with localStorage
- Implement user preferences
- Add multi-language support
- Create admin panel for content management
- Add offline functionality with service workers
- Implement user authentication and personalization

## Browser Support

- Chrome 60+
- Firefox 60+
- Safari 12+
- Edge 79+

## License

This project is part of the AgriVisionPro agricultural guidance system for Sri Lanka.