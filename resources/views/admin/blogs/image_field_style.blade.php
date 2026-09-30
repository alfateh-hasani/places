<style>
    /*
     | Blog create/edit — image field preview.
     | The default Backpack "image" field only caps the preview at max-width:100%
     | of its (half-width) column, so a portrait/large photo renders very tall.
     | Bound the preview to a compact, tidy box while letting each image keep its
     | own aspect ratio (object-fit: contain), and give it a light framed card look.
     | Loaded only on the blog form, so no other CRUD is affected.
    */
    .cropperImage [data-handle="previewArea"] {
        display: inline-block;
        max-width: 100%;
    }

    .cropperImage [data-handle="previewArea"] img[data-handle="mainImage"] {
        display: block;
        width: auto;
        max-width: 100%;
        height: auto;
        max-height: 300px;
        object-fit: contain;
        border: 1px solid #e3e6ea;
        border-radius: .5rem;
        background: #f7f8fa;
        padding: 4px;
    }
</style>
