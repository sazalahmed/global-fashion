<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StoreBlogCommentRequest;
use Modules\Ecommerce\Models\BlogPost;
use Modules\Ecommerce\Models\HomepageSection;
use Modules\Ecommerce\Services\BlogService;
use Modules\Ecommerce\Support\BuildsSeo;
use Modules\Ecommerce\Support\Seo;

class BlogController extends Controller
{
    use BuildsSeo;
    public function __construct(
        protected BlogService $blog
    ) {}

    /**
     * Display a listing of published blog posts (with optional filters).
     */
    public function index(Request $request): View
    {
        $posts = BlogPost::visible()
            ->with('author')
            ->when($request->filled('category'), fn ($q) => $q->byCategory((string) $request->input('category')))
            ->when($request->filled('tag'), fn ($q) => $q->whereJsonContains('tags', (string) $request->input('tag')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%' . $request->input('q') . '%'))
            ->orderByDesc('published_at')
            ->paginate(\Modules\Ecommerce\Models\EcommerceSetting::perPage('per_page_blog', 9))
            ->withQueryString();

        $seo = $this->staticPageSeo('blog');

        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $items = $posts->getCollection()->map(fn ($p) => [
            'name'  => $p->title,
            'url'   => route('storefront.blog.show', $p->slug),
            'image' => $p->featured_image ? url($p->featured_image) : null,
        ])->all();
        $seo->addSchema($schema->collectionPage('Blog', url()->current(), $items));
        if ($seo->breadcrumbs) { $seo->addSchema($schema->breadcrumbList($seo->breadcrumbs)); }

        return view('ecommerce::storefront.pages.blog.index', [
            'posts'        => $posts,
            'popularPosts' => $this->blog->popularPosts(3),
            'categories'   => $this->blog->categoriesWithCounts(),
            'popularTags'  => $this->blog->popularTags(),
            'seo'          => $seo,
        ]);
    }

    /**
     * Display a single blog post.
     */
    public function show(string $slug): \Illuminate\Http\RedirectResponse|View
    {
        $post = BlogPost::visible()
            ->with(['author', 'approvedComments'])
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            $current = BlogPost::currentSlugFor($slug);
            if ($current && $current !== $slug) {
                return redirect()->route('storefront.blog.show', $current, 301);
            }
            abort(404);
        }

        $seo = Seo::make()
            ->title($post->seo_title ?: $post->title)
            ->description($post->seo_description ?: Str::limit(strip_tags($post->excerpt ?: $post->content), 160))
            ->image($post->seo_image ?: $post->featured_image)
            ->type('article')
            ->breadcrumbs([
                ['name' => 'Home', 'url' => route('storefront.home')],
                ['name' => 'Blog', 'url' => route('storefront.blog.index')],
                ['name' => $post->title, 'url' => null],
            ]);

        $schema = app(\Modules\Ecommerce\Services\SeoSchemaService::class);
        $canonical = route('storefront.blog.show', $post->slug);
        $seo->addSchema($schema->blogPosting($post, $canonical))
            ->addSchema($schema->breadcrumbList($seo->breadcrumbs));

        return view('ecommerce::storefront.pages.blog.show', [
            'post'         => $post,
            'comments'     => $post->approvedComments,
            'commentCount' => $post->approvedComments->count(),
            'popularPosts' => $this->blog->popularPosts(3, $post->id),
            'categories'   => $this->blog->categoriesWithCounts(),
            'popularTags'  => $this->blog->popularTags(),
            'blogSection'  => HomepageSection::where('section_type', 'blog')->first(),
            'seo'          => $seo,
        ]);
    }

    /**
     * Store a guest comment for a post (held for moderation).
     */
    public function storeComment(StoreBlogCommentRequest $request, string $slug): RedirectResponse
    {
        $post = BlogPost::visible()->where('slug', $slug)->firstOrFail();

        $post->comments()->create([
            ...$request->validated(),
            'is_approved' => false,
        ]);

        return redirect()
            ->route('storefront.blog.show', $post->slug)
            ->with('success', __('Thank you! Your comment has been submitted and is awaiting moderation.'))
            ->withFragment('comments');
    }
}
