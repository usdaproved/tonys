<?php

class Menu extends Model{

    public function createMenuItem(int $activeState, int $category,
                                   string $name, int $price, string $description) : void {
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
                                   string $name, int $price, string $description) : void {
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

    public function removeMenuItem(int $itemID) : void {
        $sql = "SELECT * FROM order_line_items
WHERE menu_item_id = :menu_item_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":menu_item_id", $itemID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();

        // This executes if item has already been ordered.
        $sql = "UPDATE menu_items
SET category_id = NULL
WHERE id = :id;";

        if(empty($result)){
            $sql = "DELETE FROM menu_items WHERE id = :id;";
        }

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $itemID);
        $this->db->executeStatement();
    }

    public function createCategory(string $name) : void {
        $sql = "INSERT INTO menu_categories 
(name, position) VALUES (:name, :position);";

        $position = $this->getHighestCategoryPosition() + 1;

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

    public function isCategoryEmpty(int $categoryID) : bool {
        $sql = "SELECT id FROM menu_items WHERE category_id = :category_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":category_id", $categoryID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(empty($result)){
            return true;
        }

        return false;
    }

    public function removeCategory(int $categoryID) : void {
        $sql = "DELETE FROM menu_catagories WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $categoryID);
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

    public function createAddition(string $name, int $priceModifier) : void {
        $sql = "INSERT INTO additions 
(name, price_modifier) VALUES (:name, :price_modifier);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":price_modifier", $priceModifier);

        $this->db->executeStatement();
    }

    public function updateAddition(int $additionID, string $name,
                                   int $priceModifier) : void {
        $sql = "UPDATE additions
SET name = :name, price_modifier = :price_modifier WHERE id = :id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":price_modifier", $priceModifier);
        $this->db->bindValueToStatement(":id", $additionID);

        $this->db->executeStatement();
    }

    public function addAdditionToItem(int $itemID, int $additionID) : void {
        $sql = "INSERT INTO item_additions 
(item_id, addition_id, position) VALUES (:item_id, :addition_id, :position);";

        $position = $this->getHighestItemAdditionPosition($itemID) + 1;

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":item_id", $itemID);
        $this->db->bindValueToStatement(":addition_id", $additionID);
        $this->db->bindValueToStatement(":position", $position);

        $this->db->executeStatement();
    }

    public function removeAdditionFromItem(int $itemID, int $additionID) : void {
        $sql = "DELETE FROM item_additions
WHERE item_id = :item_id AND addition_id = :addition_id;";

        $this->db->beginStatement($sql);
        
        $this->db->bindValueToStatement(":item_id", $itemID);
        $this->db->bindValueToStatement(":addition_id", $additionID);

        $this->db->executeStatement();
    }

    public function removeAdditionEntirely(int $additionID) : void {
        $sql = "DELETE FROM item_additions
WHERE addition_id = :addition_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":addition_id", $additionID);
        $this->db->executeStatement();

        $sql = "SELECT * FROM line_item_additions
WHERE addition_id = :addition_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":addition_id", $additionID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();

        // This executes in the event addition was already ordered.
        $sql = "UPDATE additions
SET active = 0 WHERE id = :id;";
        
        if(empty($result)){
            // Safe to completely remove.
            $sql = "DELETE FROM additions WHERE id = :id";
        }

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $additionID);
        $this->db->executeStatement();
    }

    public function isAdditionLinkedToItem(int $additionID) : bool {
        $sql = "SELECT * FROM item_additions
WHERE addition_id = :addition_id;";
        
        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":addition_id", $additionID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(empty($result)){
            return false;
        }

        return true;
    }

    public function updateItemAdditionPosition(int $itemID, int $additionID,
                                               int $position) : void {
        $sql = "UPDATE item_additions
SET position = :position
WHERE item_id = :item_id AND addition_id = :addition_id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":item_id", $itemID);
        $this->db->bindValueToStatement(":addition_id", $additionID);
        $this->db->bindValueToStatement(":position", $position);

        $this->db->executeStatement();
    }

    public function createChoiceGroup(int $itemID, string $name,
                                      int $minPicks, int $maxPicks) : int {
        $sql = "INSERT INTO choices_parents
(name, min_picks, max_picks) VALUES (:name, :min_picks, :max_picks);";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":min_picks", $minPicks);
        $this->db->bindValueToStatement(":max_picks", $maxPicks);

        $this->db->executeStatement();

        $sql = "INSERT INTO item_choices
(item_id, choice_parent_id, position) VALUES (:item_id, :choice_parent_id, :position);";

        $groupID = $this->db->lastInsertID();
        $position = $this->getHighestItemGroupPosition($itemID) + 1;

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":item_id", $itemID);
        $this->db->bindValueToStatement(":choice_parent_id", $groupID);
        $this->db->bindValueToStatement(":position", $position);

        $this->db->executeStatement();

        return $groupID;
    }

    public function updateChoiceGroup(int $groupID, string $name,
                                      int $minPicks, int $maxPicks) : void {
        $sql = "UPDATE choices_parents
SET name = :name, min_picks = :min_picks, max_picks = :max_picks
WHERE id = :id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $groupID);
        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":min_picks", $minPicks);
        $this->db->bindValueToStatement(":max_picks", $maxPicks);
        
        $this->db->executeStatement();
    }

    public function updateChoiceGroupPosition(int $groupID, int $position) : void {
        $sql = "UPDATE item_choices 
SET position = :position 
WHERE choice_parent_id = :choice_parent_id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":choice_parent_id", $groupID);
        $this->db->bindValueToStatement(":position", $position);
        
        $this->db->executeStatement();
    }

    public function removeChoiceGroup(int $groupID) : void {
        $sql = "DELETE FROM item_choices
WHERE choice_parent_id = :choice_parent_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":choice_parent_id", $groupID);
        $this->db->executeStatement();

        // In order for this function to be called, there shouldn't be anything
        // that references this parent ID, so we should be good to remove completely.
        $sql = "DELETE FROM choices_parents
WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $groupID);
        $this->db->executeStatement();
    }

    public function createChoiceOption(int $groupID, string $name,
                                       int $priceModifier) : int {
        $sql = "INSERT INTO choices_children
(parent_id, name, price_modifier, position) 
VALUES (:parent_id, :name, :price_modifier, :position);";

        $position = $this->getHighestItemChoicePosition($groupID) + 1;

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":parent_id", $groupID);
        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":price_modifier", $priceModifier);
        $this->db->bindValueToStatement(":position", $position);
        
        $this->db->executeStatement();

        return $this->db->lastInsertID();
    }

    public function updateChoiceOption(int $choiceID, string $name,
                                       int $priceModifier) : void {
        $sql = "UPDATE choices_children
SET name = :name, price_modifier = :price_modifier 
WHERE id = :id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $choiceID);
        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":price_modifier", $priceModifier);
        
        $this->db->executeStatement();
    }

    public function updateChoiceOptionPosition(int $choiceID, int $position) : void {
        $sql = "UPDATE choices_children
SET position = :position
WHERE id = :id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $choiceID);
        $this->db->bindValueToStatement(":position", $position);

        $this->db->executeStatement();
    }

    public function removeChoiceOption(int $optionID) : void {
        $sql = "SELECT * FROM line_item_choices
WHERE choice_child_id = :choice_child_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":choice_child_id", $optionID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        // If none found, it hasn't been ordered
        // and are safe to delete completely.
        if(empty($result)){
            $sql = "DELETE FROM choices_children
WHERE id = :id;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":id", $optionID);
            $this->db->executeStatement();
        } else {
            // otherwise just disconnect them from the group.
            $sql = "UPDATE choices_children
SET parent_id = NULL
WHERE id = :id;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":id", $optionID);
            $this->db->executeStatement();
        }
    }
    
    public function getEntireMenu() : array {
        $sql = "SELECT * FROM menu_categories ORDER BY position ASC;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $categories = $this->db->getResultSet();
        // If no categories, then menu is empty.
        if(empty($categories)){
            return array();
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
            if(empty($menuItems)) {
                $menuItems = array();
            }
            
            $category["items"] = $menuItems;

            $menu[] = $category;
        }

        return $menu;
    }

    public function getCategories() : array {
        $sql = "SELECT * FROM menu_categories ORDER BY position ASC;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(empty($result)) return array();
        return $result;
    }

    public function getAddition(int $additionID) : array {
        $sql = "SELECT * FROM additions WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $additionID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }

    public function getAllAdditions() : array {
        $sql = "SELECT * FROM additions;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();
        if(empty($result)) return array();
        return $result;
    }

    public function getItemAdditions(int $itemID) : array {
        $sql = "SELECT addition_id 
FROM item_additions 
WHERE item_id = :item_id
ORDER BY position ASC;";
        
        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":item_id", $itemID);
        $this->db->executeStatement();

        $itemAdditions = $this->db->getResultSet();
        if(empty($itemAdditions)) return array();

        $additions = [];
        foreach($itemAdditions as $itemAddition){
            $addition = $this->getAddition($itemAddition["addition_id"]);
            $additions[$addition["id"]] = $addition;
        }
        
        return $additions;
    }

    public function getAllOptionsByGroupID(int $groupID) : array {
        $sql = "SELECT id, name, price_modifier 
FROM choices_children 
WHERE parent_id = :parent_id
ORDER BY position ASC;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":parent_id", $groupID);
        $this->db->executeStatement();

        return $this->db->getResultSet();
    }

    public function getChoiceGroupInfo(int $groupID) : array {
        $sql = "SELECT * FROM choices_parents WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $groupID);
        $this->db->executeStatement();

        return $this->db->getResult();
    }
    
    public function getItemNestedChoices(int $itemID) : array {
        $sql = "SELECT choice_parent_id as id 
FROM item_choices 
WHERE item_id = :item_id
ORDER BY position ASC;";
        
        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":item_id", $itemID);
        $this->db->executeStatement();

        $itemChoiceGroups = $this->db->getResultSet();
        if(empty($itemChoiceGroups)) return array();

        $choices = [];
        foreach($itemChoiceGroups as $choiceGroup){
            $choices[$choiceGroup["id"]] = $this->getChoiceGroupInfo($choiceGroup["id"]);
            $choiceChildren = $this->getAllOptionsByGroupID($choiceGroup["id"]);
            foreach($choiceChildren as $choice){
                $choices[$choiceGroup["id"]]["options"][$choice["id"]] = $choice;
            }
        }
        
        return $choices;
    }

    public function getActiveMenu() : array {
        
    }

    /**
     * Returns a list of menu items where the key => value is item_id => [Item info].
     * This is to get menu item data, when all we have is an id to go off of.
     */
    public function getMenuItems() : array {
        $sql = "SELECT * FROM menu_items;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $menuItems = $this->db->getResultSet();
        if(empty($menuItems)) return array();

        $list = [];
        foreach($menuItems as $menuItem){
            $list[$menuItem["id"]] = $menuItem;
        }
        
        return $list;
    }

    public function getDailySpecial(string $day) : array {
        $special = array();
        

        return $special;
    }
    
    public function getItemInfo(int $menuItemID) : array {
        $sql = "SELECT * FROM menu_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->executeStatement();

        $menuItem = $this->db->getResult();
        if($menuItem === false) return array();

        $menuItem["additions"] = $this->getItemAdditions($menuItemID);
        $menuItem["choices"] = $this->getItemNestedChoices($menuItemID);
        
        return $menuItem;
    }

    public function getItemNameByID(int $menuItemID) : string {
        $sql = "SELECT name FROM menu_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->executeStatement();

        return $this->db->getResult()["name"];
    }

    private function getHighestPositionInCategory(int $category) : int {
        $sql = "SELECT MAX(position) as position 
FROM menu_items WHERE category_id = :category_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":category_id", $category);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        return $result["position"] ?? 0;
    }

    private function getHighestCategoryPosition() : int {
        $sql = "SELECT MAX(position) as position FROM menu_categories;";

        $this->db->beginStatement($sql);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        return $result["position"] ?? 0;
    }

    private function getHighestItemGroupPosition(int $itemID) : int {
        $sql = "SELECT MAX(position) as position FROM item_choices WHERE item_id = :item_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":item_id", $itemID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        return $result["position"] ?? 0;
    }

    private function getHighestItemChoicePosition(int $groupID) : int {
        $sql = "SELECT MAX(position) as position 
FROM choices_children 
WHERE parent_id = :parent_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":parent_id", $groupID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        return $result["position"] ?? 0;
    }

    private function getHighestItemAdditionPosition(int $itemID) : int {
        $sql = "SELECT MAX(position) as position FROM item_additions WHERE item_id = :item_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":item_id", $itemID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        return $result["position"] ?? 0;
    }
}

?>
