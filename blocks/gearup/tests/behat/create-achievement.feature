@block @block_gearup
Feature: Testing creating an achievement
  In order to set up achievements for my students
  As a teacher
  I can create achievements

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
  Scenario: Teachers can create achievements
    Given I click on "Achievement" "link" in the "#region-main" "css_element"
    And I set the field "title" to "The First Achievement"
    And I select a visual
    And I press "Continue"
    And I press "New objective"
    And I click on "Manual" clickable element
    And I press "Save changes"
    And I press "Continue"
    And I set the field "instructions" to "The instructions are here"
    When I press "Continue"
    Then I should see "You're done!"
    And I follow "View achievement"
    And I follow "Back to the list"
    And I should see "The First Achievement"
    And I follow "The First Achievement"
    And I follow "Insights"
    And I follow "Recruits"
    And I follow "Advanced"

  @javascript
  Scenario: Teachers can navigate back in the achievement wizard
    Given I click on "Achievement" "link" in the "#region-main" "css_element"
    And I set the field "title" to "The First Achievement"
    And I select a visual
    And I press "Continue"
    And I press "New objective"
    And I click on "Manual" clickable element
    And I press "Save changes"
    And I press "Continue"
    When I follow "Identity"
    Then the field "title" matches value "The First Achievement"
    And I press "Continue"
    And I should see "Manual"
    And I press "Continue"
    And I set the field "instructions" to "The instructions are here"
    And I press "Continue"
    And I should see "You're done!"
