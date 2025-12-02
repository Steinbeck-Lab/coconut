(function () {
    function init3DViewer() {
        console.log('3D viewer init starting');

        var container = document.getElementById('viewer');
        var dataEl = document.getElementById('model-data');

        if (!container || !dataEl) {
            console.error('3D viewer: missing container or data element');
            return;
        }
        if (!window.$3Dmol) {
            console.error('3D viewer: $3Dmol not available');
            return;
        }

        // Raw content from <pre> – could be plain SDF or a JSON string with \n escapes
        var raw = dataEl.textContent || dataEl.innerText || "";
        var sdfText = raw;

        // If it starts with a quote, it's almost certainly a JSON string: "\n RDKit 3D\n..."
        try {
            var trimmed = raw.trim();
            if (trimmed.startsWith('"') && trimmed.endsWith('"')) {
                sdfText = JSON.parse(trimmed); // turns "\n..." into real newlines
                console.log('3D viewer: decoded SDF from JSON string');
            }
        } catch (e) {
            console.warn('3D viewer: JSON parse failed, using raw text', e);
            sdfText = raw;
        }

        if (!sdfText.trim()) {
            console.error('3D viewer: empty SDF text after decoding');
            return;
        }

        try {
            var viewer = $3Dmol.createViewer(container);
            viewer.setBackgroundColor(0xffffff);

            viewer.addModel(sdfText, "sdf"); // RDKit V2000
            viewer.setStyle({}, { stick: {} });
            viewer.zoomTo();
            viewer.render();

            console.log('3D viewer rendered successfully');
        } catch (e) {
            console.error('3D viewer initialization failed', e);
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        init3DViewer();
    } else {
        document.addEventListener('DOMContentLoaded', init3DViewer);
    }
})();
