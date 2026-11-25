# SwiftPress Optimizer

SwiftPress Optimizer is a WordPress performance plugin. It currently includes CSS optimization, with room to add more performance features over time.

## CSS optimization

### What is critical CSS?

Critical CSS is the small set of styles needed to render the visible part of a page when it first loads. SwiftPress generates this CSS for each page, places it directly in the page, and lets the remaining stylesheets load asynchronously.

Stylesheets can delay the browser from rendering a page. Providing the styles needed for the initial view directly can reduce that delay and help visitors see the page sooner.

### What is Minimal CSS?

Minimal CSS keeps the CSS rules used by a page and removes unused rules. Smaller stylesheets mean fewer bytes for visitors to download and less CSS for the browser to process. This can help pages load more efficiently, especially on slower connections or devices.

### Why it can help Core Web Vitals

Core Web Vitals measure real user experience across loading, responsiveness, and visual stability. Critical CSS mainly targets **Largest Contentful Paint (LCP)**, which measures how quickly the main content appears. Reducing delays from render-blocking stylesheets can help the browser render that content sooner. Minimal CSS can also support faster loading by reducing stylesheet size.

CSS optimization does not directly improve every Web Vital. **Interaction to Next Paint (INP)** measures responsiveness to user interactions, while **Cumulative Layout Shift (CLS)** measures unexpected movement on the page. Their results depend on other parts of the site too. Actual improvements vary by site, so measure your pages before and after enabling optimization.

Learn more about [Core Web Vitals](https://web.dev/articles/vitals) and [CSS performance](https://web.dev/articles/css-web-vitals).

## Current features

- Generate and cache critical CSS for WordPress pages.
- Add the cached critical styles to the page while the remaining stylesheets load asynchronously.
- Generate Minimal CSS by removing unused rules, reducing stylesheet size.

## Installation

1. Copy the `swiftpress-optimizer` folder into `wp-content/plugins/`.
2. Activate **SwiftPress Optimizer** in the WordPress Plugins screen.

## Requirements

- WordPress 6.2 or later.
- PHP 8.0 or later.
- Access to the SwiftPress CSS optimization server.
