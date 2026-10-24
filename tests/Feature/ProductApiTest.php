<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_products(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    public function test_owner_can_create_list_update_and_delete_a_product(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $created = $this->postJson('/api/products', [
            'name' => 'Kopi Arabika', 'sku' => 'KOPI-001', 'category' => 'Minuman',
            'price' => 75000, 'stock' => 20,
        ]);
        $created->assertCreated()->assertJsonPath('data.name', 'Kopi Arabika');
        $id = $created->json('data.id');

        $this->getJson('/api/products?q=Arabika&category=Minuman')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->putJson("/api/products/{$id}", ['stock' => 15])->assertOk()->assertJsonPath('data.stock', 15);
        $this->deleteJson("/api/products/{$id}")->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $id]);
    }

    public function test_user_cannot_read_or_change_another_users_product(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->for($owner)->create();
        $this->actingAs($other, 'sanctum');

        $this->getJson("/api/products/{$product->id}")->assertForbidden();
        $this->putJson("/api/products/{$product->id}", ['name' => 'Diubah'])->assertForbidden();
        $this->deleteJson("/api/products/{$product->id}")->assertForbidden();
    }

    public function test_product_photo_upload_is_stored_on_public_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->post('/api/products', [
            'name' => 'Teh', 'sku' => 'TEH-001', 'category' => 'Minuman',
            'price' => 5000, 'stock' => 10,
            'photo' => UploadedFile::fake()->image('teh.jpg'),
        ])->assertCreated();

        $product = Product::firstOrFail();
        $this->assertNotNull($product->photo_path);
        Storage::disk('public')->assertExists($product->photo_path);
    }

    public function test_invalid_product_data_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/products', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'sku', 'category', 'price', 'stock']);
    }
}
