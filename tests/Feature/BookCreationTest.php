<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_book_can_be_created_with_valid_data()
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'Dune',
            'author' => 'Frank Herbert',
            'summary' => 'Épopée de science-fiction centrée sur la planète Arrakis et l\'épice.',
            'isbn' => '9780441013593',
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/books', $payload);

        $response->assertStatus(201)->assertJsonFragment([
            'title' => 'Dune',
            'author' => 'FRANK HERBERT',
        ]);

        $this->assertDatabaseHas('books', [
            'title' => 'Dune',
            'isbn' => '9780441013593',
        ]);
    }

    public function test_a_book_is_not_created_with_invalid_data()
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'Du',
            'author' => 'FH',
            'summary' => 'Courte',
            'isbn' => '123',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['title', 'author', 'summary', 'isbn']
            ]);

        $this->assertDatabaseMissing('books', [
            'title' => 'Du',
            'isbn' => '123'
        ]);
    }

    public function test_a_book_is_not_created_if_user_is_not_authenticated()
    {
        $payload = [
            'title' => 'Dune',
            'author' => 'Frank Herbert',
            'summary' => 'Épopée de science-fiction centrée sur la planète Arrakis et l\'épice.',
            'isbn' => '9780441013593',
        ];

        $response = $this->postJson('/api/v1/books', $payload);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('books', [
            'title' => 'Dune',
            'isbn' => '9780441013593'
        ]);
    }
}
