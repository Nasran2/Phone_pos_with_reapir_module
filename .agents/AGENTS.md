# Agent Rules for phone_wholesale_rsd

- **UI Popups & Confirmations**: NEVER use native browser dialogs like `window.confirm()`, `window.alert()`, or `window.prompt()`. Always use an inline system popup. This project uses SweetAlert2 globally (`Swal.fire()`) for all confirmation dialogs, alerts, and system popups to maintain a professional, cohesive UI.
