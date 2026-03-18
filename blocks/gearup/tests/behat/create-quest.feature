@block @block_gearup
Feature: Testing creating a quest
  In order to set up quests for my students
  As a teacher
  I can create quests

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And I am on the "c1" "Course" page logged in as "t1"
    And I click on "Manage" "link" in the "Level Up Quest" "block"

  @javascript
  Scenario: Teachers can create quests
    Given I click on "Quest" "link" in the "#region-main" "css_element"
    And I set the field "title" to "The First Quest"
    And I select a visual
    And I press "Continue"
    And I press "New objective"
    And I click on "Manual" clickable element
    And I set the field "Display as" to "Objective 1"
    And I press "Save changes"
    And I press "Continue"
    And I press "New outcome"
    And I click on "Display a label" clickable element
    And I set the field "Display as" to "Outcome 1"
    And I press "Save changes"
    And I press "Continue"
    And I click on "Optional quest" "radio"
    And I press "Continue"
    And I set the following fields to these values:
      | description | The description is here |
      | instructions | The instructions are here |
      | feedback | The feedback is here |
    When I press "Continue"
    Then I should see "You're done!"
    And I follow "View quest"
    And I follow "Back to the list"
    And I should see "The First Quest"
    And I follow "The First Quest"
    And I follow "Insights"
    And I follow "Recruits"
    And I follow "Advanced"

  @javascript
  Scenario: Teachers can navigate back in the quest wizard
    Given I click on "Quest" "link" in the "#region-main" "css_element"
    And I set the field "title" to "The First Quest"
    And I select a visual
    And I press "Continue"
    And I press "New objective"
    And I click on "Manual" clickable element
    And I set the field "Display as" to "Objective 1"
    And I press "Save changes"
    And I press "Continue"
    And I press "New outcome"
    And I click on "Display a label" clickable element
    And I set the field "Display as" to "Outcome 1"
    And I press "Save changes"
    And I press "Continue"
    And I click on "Optional quest" "radio"
    And I press "Continue"
    When I follow "Identity"
    Then the field "title" matches value "The First Quest"
    And I press "Continue"
    And I should see "Objective 1"
    And I press "Continue"
    And I should see "Outcome 1"
    And I press "Continue"
    And I press "Continue"
    And I press "Continue"
    And I should see "You're done!"
