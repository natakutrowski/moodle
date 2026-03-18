@block @block_gearup
Feature: Testing browsing relationship between quests

  Background:
    Given the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
    And the following "block_gearup > quests" exist:
      | title              | course |
      | Starting quest | c1     |
      | Middle quest   | c1     |
      | Final quest    | c1     |

  @javascript
  Scenario: Relationships appear via outcome usage
    Given I activate Level Up Quest
    And I am on the "Starting quest" "block_gearup > quest" page logged in as "t1"
    And I press "New outcome"
    And I click on "Recruit for a quest" clickable element
    And I set the field "Quest" to "Middle quest"
    When I press "Save changes"
    Then I should see "Middle quest"
    And I follow "Middle quest"
    And I press "New outcome"
    And I click on "Recruit for a quest" clickable element
    And I set the field "Quest" to "Final quest"
    And I press "Save changes"
    And I should see "Final quest"
    And I am on the "Starting quest" "block_gearup > quest" page
    And "Middle quest" "link" should exist in the definition "Leads to"
    And "Comes from" "text" should not exist
    And I follow "Middle quest"
    And "Starting quest" "link" should exist in the definition "Comes from"
    And "Final quest" "link" should exist in the definition "Leads to"
    And I follow "Final quest"
    And "Middle quest" "link" should exist in the definition "Comes from"
    And "Leads to" "text" should not exist

  @javascript
  Scenario: Relationships appear via assigner usage
    Given I activate Level Up Quest
    And I am on the "Middle quest" "block_gearup > quest assign" page logged in as "t1"
    And I press "New automation"
    And I click on "Quest progression" clickable element
    And I set the field "Quest" to "Starting quest"
    And I press "Save changes"
    And I am on the "Final quest" "block_gearup > quest assign" page
    And I press "New automation"
    And I click on "Quest progression" clickable element
    And I set the field "Quest" to "Middle quest"
    And I set the field "Required state" to "Has completed"
    And I press "Save changes"
    When I am on the "Starting quest" "block_gearup > quest" page
    Then "Middle quest" "link" should exist in the definition "Leads to"
    And "Comes from" "text" should not exist
    And I follow "Middle quest"
    And "Starting quest" "link" should exist in the definition "Comes from"
    And "Final quest" "link" should exist in the definition "Leads to"
    And I follow "Final quest"
    And "Middle quest" "link" should exist in the definition "Comes from"
    And "Leads to" "text" should not exist
