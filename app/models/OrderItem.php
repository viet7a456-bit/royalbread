<?php

declare(strict_types=1);

class OrderItem
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO order_items (
                order_id, menu_item_id, menu_item_name, menu_item_image_url, quantity, price
             ) VALUES (
                :order_id, :menu_item_id, :menu_item_name, :menu_item_image_url, :quantity, :price
             )'
        );

        $stmt->execute([
            'order_id' => (int) $data['order_id'],
            'menu_item_id' => (int) $data['menu_item_id'],
            'menu_item_name' => trim((string) $data['menu_item_name']),
            'menu_item_image_url' => trim((string) ($data['menu_item_image_url'] ?? '')),
            'quantity' => (int) $data['quantity'],
            'price' => (int) $data['price'],
        ]);
    }
}
