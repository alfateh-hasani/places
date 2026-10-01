<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ApartmentRequest;
use App\Http\Requests\BlogRequest;
use App\Http\Requests\PageRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/**
 * Class ApartmentController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BlogController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Blog::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/blogs');
        CRUD::setEntityNameStrings(__('cms.page'), __('cms.blogs'));

        if (!backpack_user()->can('blog.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('blog.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('blog.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('blog.delete')) {
            $this->crud->allowAccess('delete');
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name_ar',
            'type' => 'text',
            'label' =>  __('cms.name_ar'),
        ]);
        CRUD::addColumn([
            'name' => 'name_en',
            'type' => 'text',
            'label' => __('cms.name_en'),
        ]);
        CRUD::addColumn([
            'name' => 'slug',
            'type' => 'text',
            'label' => __('cms.slug'),
        ]);
        CRUD::addColumn([
            'name' => 'image',
            'type' => 'image',
            'label' =>  __('cms.image'),
            'height' => '50px',
            'width' => '50px',
        ]);

    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(BlogRequest::class);
        // Blog::getImageAttribute() (used site-wide for the public-facing photo URL) shares
        // the 'image' name with this field, so Backpack's default value lookup ($entry->image)
        // resolves through that accessor and hands the cropper field an already-absolute URL.
        // The field template then prefixes its own disk base URL onto it, doubling the domain
        // and breaking the preview. Supplying the relative media path explicitly bypasses that.
        CRUD::field('image')
            ->label(__('cms.image'))
            ->type('image')
            ->value($this->currentImageFieldValue())
            ->withMedia([
                'collection' => 'image', // will pick the collection definition from your model
            ]);

        // Keep the image field's preview compact and tidy (the default template renders
        // the uploaded photo at full column width/height). Scoped to the blog form only.
        Widget::add([
            'type' => 'view',
            'view' => 'admin.blogs.image_field_style',
        ])->to('after_content');

        $this->crud->addField([
            'name' => 'name_ar',
            'type' => 'text',
            'label' =>  __('cms.name_ar'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' => __('cms.name_en'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        //info_ar
        $this->crud->addField([
            'name' => 'info_ar',
            'type' => 'textarea',
            'label' => __('cms.info_ar'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        //info_en
        $this->crud->addField([
            'name' => 'info_en',
            'type' => 'textarea',
            'label' => __('cms.info_en'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);



        $this->crud->addField([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' => __('cms.seo_title_ar'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'seo_title_en',
            'type' => 'text',
            'label' => __('cms.seo_title_en'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'seo_description_ar',
            'type' => 'textarea',
            'label' => __('cms.seo_description_ar'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'seo_description_en',
            'type' => 'textarea',
            'label' => __('cms.seo_description_en'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'slug',
            'type' => 'text',
            'label' => __('cms.slug'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'content_ar',
            'type' => 'quill',
            'label' =>  __('cms.content_ar'),
            'attributes' => [
                'rows' => 5,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        $this->crud->addField([
            'name' => 'content_en',
            'type' => 'quill',
            'label' =>  __('cms.content_en'),
            'attributes' => [
                'rows' => 5,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);


    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * The 'image' field's relative media path (e.g. `public/blogs/4/images/12/file.jpg`),
     * computed the same way the media library's own uploader does it. See the comment above
     * the 'image' field definition for why this needs to be supplied explicitly.
     */
    private function currentImageFieldValue(): ?string
    {
        $entry = $this->crud->getCurrentEntry();

        // On the create page Backpack returns `false` (not `null`), so the nullsafe operator
        // does not short-circuit — guard explicitly before touching the media relation.
        if (! $entry) {
            return null;
        }

        $media = $entry->getFirstMedia('image');

        if (! $media) {
            return null;
        }

        return PathGeneratorFactory::create($media)->getPath($media).$media->file_name;
    }

    //show operation
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        $this->crud->addColumn([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' => __('cms.seo_title_ar'),
        ]);
        $this->crud->addColumn([
            'name' => 'seo_title_en',
            'type' => 'text',
            'label' => __('cms.seo_title_en'),
        ]);
        $this->crud->addColumn([
            'name' => 'seo_description_ar',
            'type' => 'text',
            'label' => __('cms.seo_description_ar'),
        ]);
        $this->crud->addColumn([
            'name' => 'seo_description_en',
            'type' => 'text',
            'label' => __('cms.seo_description_en'),
        ]);
        $this->crud->addColumn([
            'name' => 'content_ar',
            'type' => 'text',
            'label' => __('cms.content_ar'),
        ]);
        $this->crud->addColumn([
            'name' => 'content_en',
            'type' => 'text',
            'label' => __('cms.content_en'),
        ]);



    }
}
