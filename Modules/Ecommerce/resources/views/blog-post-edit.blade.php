@extends('core::layouts.master')

@section('title', __("Edit Blog Post — Website"))
@section('page-title', __("Edit Blog Post"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.blog') }}">Blog</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit</span>
@endsection

@section('page-actions')
<a href="{{ route('ecommerce.blog') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Blog
</a>
@endsection

@section('content')

<form action="{{ route('ecommerce.blog.update', $post) }}" method="POST" enctype="multipart/form-data">
  @csrf
  @method('PUT')

  <div class="row g-4">
    <div class="col-xl-8">

      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-newspaper me-2"></i>Post Details</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="bp-form-label">Title *</label>
              <input type="text" class="bp-form-control" name="title" value="{{ old('title', $post->title) }}" required>
              @error('title')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12">
              <label class="bp-form-label">Excerpt</label>
              <textarea class="bp-form-control" name="excerpt" rows="2">{{ old('excerpt', $post->excerpt) }}</textarea>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Content</label>
              <textarea class="bp-form-control bp-richtext" name="content" rows="12">{{ old('content', $post->content) }}</textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-globe me-2 text-secondary"></i>SEO</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="bp-form-label">SEO Title</label>
              <input type="text" class="bp-form-control @error('seo_title') is-invalid @enderror" name="seo_title" data-seo-title value="{{ old('seo_title', $post->seo_title ?? '') }}" placeholder="SEO page title (defaults to post title)">
              <small class="fs-11 text-muted" data-seo-title-count></small>
              @error('seo_title')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12">
              <label class="bp-form-label">SEO Description</label>
              <textarea class="bp-form-control @error('seo_description') is-invalid @enderror" name="seo_description" data-seo-desc rows="2" placeholder="Short description for search engines (max 160 chars)">{{ old('seo_description', $post->seo_description ?? '') }}</textarea>
              <small class="fs-11 text-muted" data-seo-desc-count></small>
              @error('seo_description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12">
              <div class="bp-seo-snippet" data-seo-preview>
                <div class="seo-pv-title"></div>
                <div class="seo-pv-url">{{ url('/') }}/…</div>
                <div class="seo-pv-desc"></div>
              </div>
            </div>
            <div class="col-12">
              <x-core::image-upload
                name="seo_image"
                id="seoImage"
                label="SEO Image (Open Graph / social share image)"
                hint="JPG, PNG, WebP — max 2MB. Recommended: 1200×630px."
                :current="($post->seo_image ?? null) ? upload_url($post->seo_image) : null"
              />
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="col-xl-4">

      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2"></i>Publish</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="bp-form-label">Status</label>
              <select class="bp-form-select w-100" name="is_published">
                <option value="0" {{ !old('is_published', $post->is_published) ? 'selected' : '' }}>Draft</option>
                <option value="1" {{ old('is_published', $post->is_published) ? 'selected' : '' }}>Published</option>
              </select>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Show on Homepage</label>
              <select class="bp-form-select w-100" name="show_on_homepage">
                <option value="0" {{ !old('show_on_homepage', $post->show_on_homepage) ? 'selected' : '' }}>No</option>
                <option value="1" {{ old('show_on_homepage', $post->show_on_homepage) ? 'selected' : '' }}>Yes</option>
              </select>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Publish Date</label>
              <input type="datetime-local" class="bp-form-control" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2"></i>Categorization</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="bp-form-label">Category</label>
              <select class="bp-form-select w-100" name="blog_category_id">
                <option value="">Select category</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ old('blog_category_id', $post->blog_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
              </select>
              @error('blog_category_id')
                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
              @enderror
              <div class="fs-11 text-muted mt-1">Manage the list in <a href="{{ route('ecommerce.blog-categories') }}">Blog Categories</a>.</div>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Tags</label>
              <input type="text" class="bp-form-control" name="tags" data-tagify value="{{ old('tags', is_array($post->tags) ? implode(', ', $post->tags) : $post->tags) }}">
              <div class="fs-11 text-muted mt-1">Comma-separated</div>
            </div>
          </div>
        </div>
      </div>

      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-image me-2"></i>Featured Image</h5>
        </div>
        <div class="bp-card-body">
          <x-core::image-upload
            name="featured_image"
            id="featuredImage"
            label="Featured Image"
            :current="$post->featured_image ? upload_url($post->featured_image) : null"
          />
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="submit" class="bp-btn bp-btn-success justify-content-center">
          <i class="fa-solid fa-save me-2"></i> Update Post
        </button>
        <a href="{{ route('ecommerce.blog') }}" class="bp-btn bp-btn-danger justify-content-center">
          <i class="fa-solid fa-times me-2"></i> Cancel
        </a>
      </div>

    </div>
  </div>

</form>

@endsection

@push('scripts')
<script src="{{ asset('website/assets/js/seo-snippet.js') }}"></script>
@endpush
