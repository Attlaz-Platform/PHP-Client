<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\MarketPulse\Endpoint\VendorProductEndpoint;
use Attlaz\MarketPulse\Model\StockStatus;
use Attlaz\MarketPulse\Model\VendorProduct;
use PHPUnit\Framework\TestCase;

/**
 * Guards that every field on VendorProduct actually reaches the wire.
 *
 * `variant_axes` shipped in `saveProduct` and in the parser but was missing from `updateProduct`, so
 * an import that upserts wrote it only for products that did not exist yet — silently, for a whole
 * catalog. Nothing typed catches that: a forgotten patch operation is well-formed code.
 *
 * Like PathTest, and unlike the rest of this suite, this needs no credentials and no network.
 */
class VendorProductEndpointTest extends TestCase
{
    /** Fields that legitimately never appear in a request body, and why. */
    private const NOT_SENT = [
        // `vendorId` is a path parameter; the rest are assigned by the API.
        'save' => ['id', 'vendorId', 'createdAt', 'updatedAt'],
        // Same, plus: `id` is in the URL, and `identifier` is the upsert key, so it is not patchable.
        'update' => ['id', 'vendorId', 'identifier', 'createdAt', 'updatedAt'],
    ];

    public function testSaveSendsEveryFieldTheApiOwns(): void
    {
        $endpoint = new RecordingVendorProductEndpoint(new Client());
        $endpoint->response = $this->completeRecord();
        $product = $this->filledProduct();

        $endpoint->saveProduct($product);

        $sentValues = \array_values($endpoint->sent[0]['body']);
        foreach ($this->fieldNames() as $field) {
            if (\in_array($field, self::NOT_SENT['save'], true)) {
                continue;
            }
            $this->assertContains($this->wireValue($product->{$field}), $sentValues, 'saveProduct() does not send `' . $field . '`');
        }
    }

    public function testUpdateSendsEveryMutableField(): void
    {
        $endpoint = new RecordingVendorProductEndpoint(new Client());
        $product = $this->filledProduct();

        $endpoint->updateProduct($product);

        $patched = \array_column($endpoint->sent[0]['body'], 'value');
        foreach ($this->fieldNames() as $field) {
            if (\in_array($field, self::NOT_SENT['update'], true)) {
                continue;
            }
            $this->assertContains($this->wireValue($product->{$field}), $patched, 'updateProduct() does not patch `' . $field . '`');
        }
    }

    /**
     * The reverse direction, read back through saveProduct's own return value. A field the API
     * returns but the parser forgets stays uninitialised, which names itself without listing the
     * fields a second time.
     */
    public function testParseFillsEveryFieldFromACompleteResponse(): void
    {
        $endpoint = new RecordingVendorProductEndpoint(new Client());
        $endpoint->response = $this->completeRecord();

        $parsed = $endpoint->saveProduct($this->filledProduct());

        $reflection = new \ReflectionClass(VendorProduct::class);
        foreach ($this->fieldNames() as $field) {
            $property = $reflection->getProperty($field);
            $this->assertTrue($property->isInitialized($parsed), 'the parser leaves `' . $field . '` unset');
            $this->assertNotNull($property->getValue($parsed), 'the parser leaves `' . $field . '` null');
        }
    }

    /** @return string[] every public field on the model, so a new one is covered without editing this list */
    private function fieldNames(): array
    {
        $names = [];
        foreach ((new \ReflectionClass(VendorProduct::class))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $names[] = $property->getName();
        }

        return $names;
    }

    /** An enum travels as its backing value; everything else is sent as-is. */
    private function wireValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    /** Every field set, each to a value no other field uses, so one field cannot stand in for another. */
    private function filledProduct(): VendorProduct
    {
        $product = new VendorProduct();
        $product->id = 'ID1';
        $product->vendorId = 'VENDOR1';
        $product->identifier = 'IDENTIFIER1';
        $product->name = 'NAME1';
        $product->image = 'IMAGE1';
        $product->gtin = 'GTIN1';
        $product->brand = 'BRAND1';
        $product->url = 'URL1';
        $product->sku = 'SKU1';
        $product->manufacturerPartNumber = 'MPN1';
        $product->variantGroup = 'GROUP1';
        $product->variantAxes = [['name' => 'Colour', 'value' => 'Jet Black']];
        $product->currency = 'EUR';
        $product->price = 101.0;
        $product->originalPrice = 102.0;
        $product->shippingCost = 103.0;
        $product->stockStatus = StockStatus::InStock;
        $product->properties = ['material' => 'steel'];
        $product->createdAt = new \DateTime('2026-01-01T00:00:00.000+00:00');
        $product->updatedAt = new \DateTime('2026-01-02T00:00:00.000+00:00');

        return $product;
    }

    /** @return array<string, mixed> what the API returns for a fully populated product */
    private function completeRecord(): array
    {
        return [
            'id' => 'ID1',
            'vendor' => 'VENDOR1',
            'identifier' => 'IDENTIFIER1',
            'name' => 'NAME1',
            'image' => 'IMAGE1',
            'gtin' => 'GTIN1',
            'brand' => 'BRAND1',
            'url' => 'URL1',
            'sku' => 'SKU1',
            'manufacturer_part_number' => 'MPN1',
            'variant_group' => 'GROUP1',
            'variant_axes' => [['name' => 'Colour', 'value' => 'Jet Black']],
            'currency' => 'EUR',
            'price' => 101.0,
            'original_price' => 102.0,
            'shipping_cost' => 103.0,
            'stock_status' => 'in_stock',
            'properties' => ['material' => 'steel'],
            'created_at' => '2026-01-01T00:00:00.000+00:00',
            'updated_at' => '2026-01-02T00:00:00.000+00:00',
        ];
    }
}

/** Records the finished body instead of sending it. Intercepts above createRequest, which authenticates. */
class RecordingVendorProductEndpoint extends VendorProductEndpoint
{
    /** @var array<int, array{uri: string, body: mixed, method: string}> */
    public array $sent = [];
    /** @var array<string, mixed>|null */
    public array|null $response = null;

    public function requestObject(string $uri, array|object|null $body = null, string $method = 'GET'): array|null
    {
        $this->sent[] = ['uri' => $uri, 'body' => $body, 'method' => $method];

        return $this->response ?? [];
    }
}
