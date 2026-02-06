import { Application } from '@hotwired/stimulus';

const app = new Application();
app.load(
    Object.entries(import.meta.glob('./**/*_controller.{js,ts}', { eager: true }))
        .map(([path, module]) => ({ 
            identifier: path
                .replace(/(.*?)\/(.*?)_controller\.(js|ts)$/, '$2')
                .replace(/_/g, '-'),
            controller: module.default 
        }))
);

window.Stimulus = app;
