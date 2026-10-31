@block @block_gearup @javascript
Feature: Reordering objectives
  In order to present objectives in a useful order
  As a manager
  I can move objectives up and down

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
    And the following "course enrolments" exist:
      | user | course | role           |
      | t1   | c1     | editingteacher |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And the following "block_gearup > missions" exist:
      | title         | course | type                              | visual   |
      | Ordered quest | c1     | block_gearup\local\mission\quest | person-1 |

  Scenario: An empty mission has no ordering actions
    When I am on the "Ordered quest" "block_gearup > mission" page logged in as "t1"
    Then I should see "No objectives"
    And "Move up" "button" should not exist
    And "Move down" "button" should not exist
    And "New objective" "button" should exist

  Scenario: A single objective can be edited but cannot be moved
    Given the following "block_gearup > objectives" exist:
      | mission       | type   | label |
      | Ordered quest | manual | Alpha |
    When I am on the "Ordered quest" "block_gearup > mission" page logged in as "t1"
    And I click on "Actions" "button" in the "Alpha" "block_gearup > objective"
    Then "Edit" "button" should exist in the "Alpha" "block_gearup > objective"
    And "Move up" "button" should not exist in the "Alpha" "block_gearup > objective"
    And "Move down" "button" should not exist in the "Alpha" "block_gearup > objective"

  Scenario: A manager can swap two objectives in both directions
    Given the following "block_gearup > objectives" exist:
      | mission       | type   | label |
      | Ordered quest | manual | Alpha |
      | Ordered quest | manual | Bravo |
    And I am on the "Ordered quest" "block_gearup > mission" page logged in as "t1"
    And I click on "Actions" "button" in the "Alpha" "block_gearup > objective"
    And "Move up" "button" should not exist in the "Alpha" "block_gearup > objective"
    When I click on "Move down" "button" in the "Alpha" "block_gearup > objective"
    Then "Bravo" "text" should appear before "Alpha" "text"
    And I click on "Actions" "button" in the "Alpha" "block_gearup > objective"
    And "Edit" "button" should exist in the "Alpha" "block_gearup > objective"
    And "Move down" "button" should not exist in the "Alpha" "block_gearup > objective"
    And I reload the page
    And "Bravo" "text" should appear before "Alpha" "text"
    And I click on "Actions" "button" in the "Alpha" "block_gearup > objective"
    And I click on "Move up" "button" in the "Alpha" "block_gearup > objective"
    And "Alpha" "text" should appear before "Bravo" "text"
    And I click on "Actions" "button" in the "Alpha" "block_gearup > objective"
    And "Move up" "button" should not exist in the "Alpha" "block_gearup > objective"
    And "Move down" "button" should exist in the "Alpha" "block_gearup > objective"
    And I reload the page
    And "Alpha" "text" should appear before "Bravo" "text"

  Scenario: A manager can move a middle objective to either end
    Given the following "block_gearup > objectives" exist:
      | mission       | type   | label   |
      | Ordered quest | manual | Alpha   |
      | Ordered quest | manual | Bravo   |
      | Ordered quest | manual | Charlie |
      | Ordered quest | manual | Delta   |
    And I am on the "Ordered quest" "block_gearup > mission" page logged in as "t1"
    And I click on "Actions" "button" in the "Charlie" "block_gearup > objective"
    And "Move up" "button" should exist in the "Charlie" "block_gearup > objective"
    And "Move down" "button" should exist in the "Charlie" "block_gearup > objective"
    When I click on "Move up" "button" in the "Charlie" "block_gearup > objective"
    Then "Alpha" "text" should appear before "Charlie" "text"
    And "Charlie" "text" should appear before "Bravo" "text"
    And "Bravo" "text" should appear before "Delta" "text"
    And I click on "Actions" "button" in the "Charlie" "block_gearup > objective"
    And I click on "Move up" "button" in the "Charlie" "block_gearup > objective"
    And "Charlie" "text" should appear before "Alpha" "text"
    And I click on "Actions" "button" in the "Charlie" "block_gearup > objective"
    And "Move up" "button" should not exist in the "Charlie" "block_gearup > objective"
    And I click on "Move down" "button" in the "Charlie" "block_gearup > objective"
    And I click on "Actions" "button" in the "Charlie" "block_gearup > objective"
    And I click on "Move down" "button" in the "Charlie" "block_gearup > objective"
    And I click on "Actions" "button" in the "Charlie" "block_gearup > objective"
    And I click on "Move down" "button" in the "Charlie" "block_gearup > objective"
    And "Alpha" "text" should appear before "Bravo" "text"
    And "Bravo" "text" should appear before "Delta" "text"
    And "Delta" "text" should appear before "Charlie" "text"
    And I click on "Actions" "button" in the "Charlie" "block_gearup > objective"
    And "Move down" "button" should not exist in the "Charlie" "block_gearup > objective"
    And "Edit" "button" should exist in the "Charlie" "block_gearup > objective"
    And I reload the page
    And "Alpha" "text" should appear before "Bravo" "text"
    And "Bravo" "text" should appear before "Delta" "text"
    And "Delta" "text" should appear before "Charlie" "text"
