# AI Chat Assistant – WordPress Plugin

A WordPress plugin that integrates ChatGPT directly into any page or post 
using a simple shortcode.

## Features

- **ChatGPT Integration** – connects directly to OpenAI API
- **Admin Settings Panel** – configure API key, model and behavior
- **Customizable** – adjust temperature, max tokens and system prompt
- **Responsive** – works on mobile and desktop
- **Secure** – nonce verification, input sanitization, XSS protection
- **Easy to use** – just add `[ai_chat]` shortcode anywhere

## Tech Stack

- PHP (WordPress Plugin API, OOP)
- JavaScript (jQuery, AJAX)
- CSS3 (Flexbox, Responsive)
- OpenAI API (GPT-4o, GPT-4o Mini)

## Installation

1. Download the plugin folder
2. Upload to `/wp-content/plugins/`
3. Activate in WordPress Admin → Plugins
4. Go to **AI Chat** in the admin menu
5. Enter your OpenAI API key
6. Add `[ai_chat]` to any page or post

## Screenshots

![Chat Widget](screenshots/chat-widget.png)

## Security

- API key is stored securely in WordPress database
- All requests verified with WordPress nonce
- User input sanitized with `sanitize_text_field()`
- Output escaped with `escapeHtml()`

## Author

**Dmytro Popov** – Junior Web Developer, Hamburg  
[GitHub](https://github.com/Dmytro-Popov) · 
[LinkedIn](https://www.linkedin.com/in/dmytro-popov-4559813b4/)
