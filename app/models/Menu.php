<?php

class Menu extends Model{

    // here is how we do specials
    // two fields are added to the menu items.
    // one is to mark it special only,
    // second is to mark what day it is if any day at all.
    // Then for the main menu we grab only items that are not special only items.
    // then we grab specials seperately. There can be more than one daily special this way.
    // Then if the special is not an special only item, that means it exists within the regular menu as well.
    // We then remove it from the regular menu.
    // Then we display specials up top and carry on as normal with the menu.

    public function createMenuItem(int $activeState, int $category,
                                   string $name, int $price, string $description) : int {
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

        return $this->db->lastInsertID();
    }

    public function updateMenuItem(int $menuItemID, int $activeState, int $category,
                                   string $name, int $price, string $description) : void {
        $sql = "UPDATE menu_items 
SET active = :active, category_id = :category_id, name = :name, price = :price, 
description = :description
WHERE id = :id";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->bindValueToStatement(":active", $activeState);
        $this->db->bindValueToStatement(":category_id", $category);
        $this->db->bindValueToStatement(":name", $name);
        $this->db->bindValueToStatement(":price", $price);
        $this->db->bindValueToStatement(":description", $description);
        
        $this->db->executeStatement();
    }

    public function removeMenuItem(int $itemID) : void {
        $sql = "SELECT * FROM order_line_items
WHERE menu_item_id = :menu_item_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":menu_item_id", $itemID);
        $this->db->executeStatement();

        $orderResult = $this->db->getResultSet();

        // Go through the item and remove every option and group.
        $itemInfo = $this->getItemInfo($itemID);

        foreach($itemInfo['choices'] as $choice){
            foreach($choice['options'] as $option){
                $this->removeChoiceOption($option['id']);
            }

            $this->removeChoiceGroup($choice['id']);
        }

        // This executes if item has already been ordered.
        $sql = "UPDATE menu_items
SET category_id = NULL
WHERE id = :id;";

        if(empty($orderResult)){
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
        $sql = "DELETE FROM menu_categories WHERE id = :id;";

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
        $sql = "SELECT * FROM line_item_choices
WHERE choice_parent_id = :choice_parent_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":choice_parent_id", $groupID);
        $this->db->executeStatement();

        $result = $this->db->getResultSet();

        // This is always safe to call, as it just disconnects it from being loaded in the menu.
        // No information is lost to the orders.

        $sql = "DELETE FROM item_choices
WHERE choice_parent_id = :choice_parent_id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":choice_parent_id", $groupID);
        $this->db->executeStatement();

        // If none found, it hasn't been ordered
        // and are safe to delete completely.
        if(empty($result)){
            $sql = "DELETE FROM choices_parents WHERE id = :id;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":id", $groupID);
            $this->db->executeStatement();
        }
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

    public function setChoiceOptionActiveState(int $choiceID, int $active) : void {
        $sql = "UPDATE choices_children SET active = :active WHERE id = :id;";

        $this->db->beginStatement($sql);

        $this->db->bindValueToStatement(":id", $choiceID);
        $this->db->bindValueToStatement(":active", $active);
        
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
    
    public function getEntireMenu(int $day = NULL) : array {
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
            // I know this isn't the most elegant solution.
            // But sometimes we want to get the full menu and not care about specials.
            $sql = "SELECT * FROM menu_items
WHERE category_id = :category_id AND special_only = 0 ";
            if(!is_null($day)){
                $sql .= "AND (special_day != :day OR special_day != :day IS NULL) ";
            }
            $sql .= "ORDER BY position ASC;";

            $this->db->beginStatement($sql);
            $this->db->bindValueToStatement(":category_id", $category["id"]);
            if(!is_null($day)){
                $this->db->bindValueToStatement(":day", $day);
            }
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

    public function getAllOptionsByGroupID(int $groupID) : array {
        $sql = "SELECT id, name, price_modifier, active, special_price_modifier 
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
            $choices[$choiceGroup["id"]]["options"] = array();
            foreach($choiceChildren as $choice){
                $choices[$choiceGroup["id"]]["options"][$choice["id"]] = $choice;
            }
        }
        
        return $choices;
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

    public function setSpecialOnly(int $menuItemID, int $isSpecialOnly) : void {
        $sql = "UPDATE menu_items SET special_only = :special_only WHERE id = :id;";
        
        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->bindValueToStatement(":special_only", $isSpecialOnly);
        $this->db->executeStatement();
    }

    public function setSpecialDay(int $menuItemID, int $day = NULL) : void {
        $sql = "UPDATE menu_items SET special_day = :special_day WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->bindValueToStatement(":special_day", $day);
        $this->db->executeStatement();
    }

    public function setSpecialPrice(int $menuItemID, int $price = NULL) : void {
        $sql = "UPDATE menu_items SET special_price = :special_price WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->bindValueToStatement(":special_price", $price);
        $this->db->executeStatement();
    }

    // NOTE(Trystan): Option is = to choice here.
    public function setSpecialOptionPrice(int $optionID, int $price = NULL) : void{
        $sql = "UPDATE choices_children 
SET special_price_modifier = :special_price_modifier
WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $optionID);
        $this->db->bindValueToStatement(":special_price_modifier", $price);
        $this->db->executeStatement();
    }

    public function getDailySpecial(int $day) : array {
        $sql = "SELECT * FROM menu_items WHERE special_day = :special_day;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":special_day", $day);
        $this->db->executeStatement();
        
        $result = $this->db->getResultSet();

        if(is_bool($result)) return array();
        return $result;
    }
    
    public function getItemInfo(int $menuItemID) : array {
        $sql = "SELECT * FROM menu_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->executeStatement();

        $menuItem = $this->db->getResult();
        if(is_bool($menuItem)) $menuItem = array();

        $menuItem["choices"] = $this->getItemNestedChoices($menuItemID);
        
        return $menuItem;
    }

    public function isItemActive(int $menuItemID) : bool {
        $sql = "SELECT active FROM menu_items WHERE id = :id;";

        $this->db->beginStatement($sql);
        $this->db->bindValueToStatement(":id", $menuItemID);
        $this->db->executeStatement();

        $result = $this->db->getResult();
        if($result["active"] == 1) return true;
        return false;
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
}

?>
