@block @block_gearup
Feature: Testing unlocking an achievement
  In order for students to unlock achievements
  As a teacher
  I need to set them up

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
      | s1       | Student   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
      | s1       | c1     | student      |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And the following "block_gearup > achievements" exist:
      | title              | course | instructions       |
      | Ultra Achievement  | c1     | This is what to do |
    And the following "block_gearup > recruits" exist:
      | mission              | user   |
      | Ultra Achievement    | s1     |

  @javascript
  Scenario: Teachers can help students unlock achievements
    Given I am on the "c1" "Course" page logged in as "s1"
    And I should see "Ultra Achievement"
    And I should see "0%" in the "Level Up Quest" "block"
    And I click on "Ultra Achievement" "link" in the "Level Up Quest" "block"
    And ".modal .block_gearup time" "css_element" should not exist

    And I am on the "c1" "Course" page logged in as "s2"
    And I should not see "Ultra Achievement"

    And I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I follow "Student One"
    And I press "Increment"
    And I press "Confirm"

    And I am on the "c1" "Course" page logged in as "s1"
    And I wait "2" seconds
    Then I should see "Achievement unlocked"
    And I click on "Achievement unlocked" clickable element
    And I should see "Ultra Achievement" in the ".modal .block_gearup" "css_element"
    And I press "Awesome!"
    And I should see "Ultra Achievement" in the "Level Up Quest" "block"
    And I should not see "0%" in the "Level Up Quest" "block"
    And I click on "Ultra Achievement" "link" in the "Level Up Quest" "block"
    And ".modal .block_gearup time" "css_element" should exist
