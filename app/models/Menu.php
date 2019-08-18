<?php

require_once APP_ROOT . "/models/Model.php";

class Menu extends Model{

    public function createMenuItem(int $activeState, int $category,
                                   string $name, float $price, string $description) : void {
        $sql = "INSERT INTO menu_items 
(active, category_id, name, price, description, position) 
VALUES (:active, :category_id, :name, :price, :description, :position);";

        $position = $this->getHighestPositionInCategory($category) + 1;

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":active", $activeState);
        $this->db->bindValueToStatement(":category_id", $category);
        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":price", $price);
        $this->db->bindValueToStatement(":description", $description);
        $this->db->bindValueToStatement(":position", $position);
        
        $this->db->executeStatement();
    }

    public function updateMenuItem(int $menuItemID, int $activeState, int $category,
                                   string $name, float $price, string $description) : void {
        $sql = "UPDATE menu_items 
SET active = :active, category_id = :category_id, name = :name, price = :price, 
description = :description, position = :position
WHERE id = :id";

        $position = $this->getHighestPositionInCategory($category) + 1;

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->bindValueToStatement(":active", $activeState);
        $this->db->bindValueToStatement(":category_id", $category);
        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":price", $price);
        $this->db->bindValueToStatement(":description", $description);
        $this->db->bindValueToStatement(":position", $position);
        
        $this->db->executeStatement();
    }

    public function createCategory(string $name) : void {
        $sql = "INSERT INTO menu_categories 
(name, position) VALUES (:name, :position);";

        $position = $this->getHighestCategoryPosition();

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":position", $position);
        
        $this->db->executeStatement();
    }

    public function updateCategory(int $categoryID, string $name) : void {
        $sql = "UPDATE menu_categories 
SET name = :name WHERE id = :id";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $categoryID);
        $this->db->bindValueToStatement(":name", $name);
        
        $this->db->executeStatement();
    }

    public function updateCategoryPosition(int $categoryID, int $position) : void {
        $sql = "UPDATE menu_categories
SET position = :position WHERE id = :id";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $categoryID);
        $this->db->bindValueToStatement(":position", $position);
        
        $this->db->executeStatement();
    }

    public function updateItemPosition(int $itemID, int $categoryID, int $position) : void {
        $sql = "UPDATE menu_items
SET category_id = :category_id, position = :position WHERE id = :id";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $itemID);
        $this->db->bindValueToStatement(":category_id", $categoryID);
        $this->db->bindValueToStatement(":position", $position);
        
        $this->db->executeStatement();
    }

    // TODO: The output array for this function should look like an array of arrays
    // Where
    // CategoryPos1 => (menuItemArrayPos1, menuItemArrayPos2 ... )
    // CategoryPos2 => ...
    // And so on.
    public function getEntireMenu() : array {
        $sql = "SELECT * FROM menu_categories ORDER BY position ASC;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $categories = $this->db->getResultSet();
        // If no categories, then menu is empty.
        if(is_bool($categories)){
            return NULL;
        }
        
        $menu = [];
        foreach($categories as $category){
            $sql = "SELECT * FROM menu_items
WHERE category_id = :category_id
ORDER BY position ASC;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":category_id", $category["id"]);
            $this->db->executeStatement();

            $menuItems = $this->db->getResultSet();
            // We want to be able to display a category name even if no items.
            if(is_bool($menuItems)) {
                $menuItems = NULL;
            }
            $category["items"] = $menuItems;

            $menu[] = $category;
        }

        return $menu;
    }

    public function getCategories() : ?array {
        $sql = "SELECT * FROM menu_categories ORDER BY position ASC;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(is_bool($result)) return NULL;
        return $result;
    }

    public function getActiveMenu() : array {
        
    }

    public function getMenuItemInfoByID(int $menuItemID) : ?array {
        $sql = "SELECT * FROM menu_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->executeStatement();

        $menuItem = $this->db->getResult();
        if(is_bool($menuItem)) return NULL;
        return $menuItem;
    }

    // TODO: Come back to this funciton.
    // Should it even be in "Menu"?
    // Is there a more efficient MySQL based way of doing this?
    // TODO: This shouldn't exist.
    // We should just have getItemPricesByItemNames(array $itemNames)
    public function calculateTotalPrice(array $order) : float {
        $totalPrice = 0.00;
        
        foreach($order as $key => $value){
            if($value && $value > 0){
                $sql = "SELECT price FROM menu_items WHERE name = :name";
                
                $this->db->beginStatement($sql);
                $this->db->bindValueToStatement(":name", $key);
                $this->db->executeStatement();

                $itemPrice = $this->db->getResult();
                $itemPrice = $itemPrice["price"];

                if(!is_bool($itemPrice)) $totalPrice += $itemPrice * $value;
                
            }
        }

        // TODO: Taxes.
        return $totalPrice;
    }

    public function getItemNameByID(int $menuItemID) : string {
        $sql = "SELECT name FROM menu_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->executeStatement();

        return $this->db->getResult()["name"];
    }

    private function getHighestPositionInCategory(int $category) : ?int {
        $sql = "SELECT MAX(position) FROM menu_items WHERE category_id = :category_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":category_id", $category);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return NULL;
        return $result["MAX(position)"];
    }

    private function getHighestCategoryPosition() : ?int {
        $sql = "SELECT MAX(position) FROM menu_categories;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if(is_bool($result)) return NULL;
        return $result["MAX(position)"];
    }
}

?>
