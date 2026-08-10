@block @block_gearup @javascript
Feature: Testing modal forms
  In order to configure missions
  As a teacher
  I can use modal forms

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
    And the following "block_gearup > quests" exist:
      | title         | course |
      | Modal Quest   | c1     |
    And I am on the "Modal Quest" "block_gearup > quest" page logged in as "t1"

  Scenario: Teachers can submit modal forms
    When I click on "Edit" "button" in the definition "List of objectives"
    Then the field "Display as" in the "Edit objective" "dialogue" matches value "Objective 1"
    And I set the field "Display as" in the "Edit objective" "dialogue" to "Updated objective"
    And I click on "Save changes" "button" in the "Edit objective" "dialogue"
    And I should see "Updated objective"
    And I should not see "Objective 1"

  Scenario: Teachers can use the modal form delete button
    When I click on "Edit" "button" in the definition "List of objectives"
    Then "Delete" "button" should exist in the "Edit objective" "dialogue"
    And I click on "Delete" "button" in the "Edit objective" "dialogue"
    And I click on "Yes" "button" in the "Confirm" "dialogue"
    And I should see "No objectives"
    And I should not see "Objective 1"
