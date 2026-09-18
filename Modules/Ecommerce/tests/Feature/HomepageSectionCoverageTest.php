<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\View;
use Modules\Ecommerce\Support\HomepageSectionSchema;
use Tests\TestCase;

/**
 * Drift guard: keeps the homepage section pieces in sync so a section can never
 * be defined in one place but silently missing in another (the class of bug
 * where `trending`/`special_brand`/`favourite` were handled in code but not
 * seeded, so they never rendered).
 */
class HomepageSectionCoverageTest extends TestCase
{
    /** Every section type with an editable-content schema must have a home partial. */
    public function test_every_schema_section_has_a_partial(): void
    {
        foreach (array_keys(HomepageSectionSchema::all()) as $type) {
            $this->assertTrue(
                View::exists("ecommerce::storefront.pages.home.partials.{$type}"),
                "Missing homepage partial for section type [{$type}]",
            );
        }
    }

    /** Every schema field is well-formed (key/label/type/group/default present). */
    public function test_schema_fields_are_well_formed(): void
    {
        $allowedTypes = ['text', 'textarea', 'image', 'link', 'number', 'switch', 'highlight'];

        foreach (HomepageSectionSchema::all() as $type => $fields) {
            foreach ($fields as $f) {
                foreach (['key', 'label', 'type', 'group', 'default'] as $attr) {
                    $this->assertArrayHasKey($attr, $f, "[$type] field missing [$attr]");
                }
                $this->assertContains($f['type'], $allowedTypes, "[$type] field [{$f['key']}] has unknown type [{$f['type']}]");
            }
        }
    }

    /** Link resolver: preset route -> URL, custom URL pass-through, null -> '#'. */
    public function test_link_resolver(): void
    {
        $this->assertSame('#', HomepageSectionSchema::resolveLink(null));
        $this->assertSame('https://example.test/x', HomepageSectionSchema::resolveLink('https://example.test/x'));
        $this->assertStringContainsString('/shop', HomepageSectionSchema::resolveLink('storefront.shop.index'));
    }
}
