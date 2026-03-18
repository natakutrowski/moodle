@block @block_gearup @javascript
Feature: Testing tracking of profile field values
  In order to track profile field values
  As a teacher
  I can use an objective

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname | enablecompletion |
      | Course 1  | c1        | 1                |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
      | s1       | Student   | One      |
      | s2       | Student   | Two      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
      | s1       | c1     | student        |
      | s2       | c1     | student        |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And the following "custom profile fields" exist:
      | datatype  | name             | shortname       | locked | visible | defaultdata |
      | text      | textwithdefault  | textwithdefault |        | 2       | 5           |
      | text      | textlocked       | textlocked      | 1      | 2       |             |
      | checkbox  | checkbox         | checkbox        |        | 2       | 0           |
    And the following "block_gearup > achievements" exist:
      | title          | course | instructions       |
      | Achievement 1  | c1     | This is what to do |
    And I am on the "Achievement 1" "block_gearup > achievement" page logged in as "t1"
    And I click on "Edit" "button" in the definition "List of objectives"
    And I click on "Delete" "button" in the "Edit objective" "dialogue"
    And I click on "Yes" "button" in the "Confirm" "dialogue"

  Scenario: Profile field value expect exact number
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | textwithdefault  |
      | Treat value as      | Number           |
      | cd_cond_number      | Equals to        |
      | cd_value_number     | 10               |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "10"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    When I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    Then I should not see "%" in the ".modal" "css_element"

  Scenario: Profile field value expect exact number with tracking
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | textwithdefault  |
      | Treat value as      | Number           |
      | cd_cond_number      | Equals to        |
      | cd_value_number     | 10               |
      | cd_track            | 1                |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "1"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "10%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "10"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    When I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    Then I should not see "0%" in the ".modal" "css_element"

  Scenario: Profile field value expect exact number with tracking and keep best
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | textwithdefault  |
      | Treat value as      | Number           |
      | cd_cond_number      | Equals to        |
      | cd_value_number     | 10               |
      | cd_track            | 1                |
      | cd_keepbest         | 1                |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "1"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "10"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    When I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    Then I should not see "0%" in the ".modal" "css_element"

  Scenario: Profile field value expect greater than or equal number
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | textwithdefault  |
      | Treat value as      | Number           |
      | cd_cond_number      | Is greater than or equal to |
      | cd_value_number     | 10               |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "15"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    When I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    Then I should not see "%" in the ".modal" "css_element"

  Scenario: Profile field value expect greater than or equal number with tracking
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | textwithdefault  |
      | Treat value as      | Number           |
      | cd_cond_number      | Is greater than or equal to |
      | cd_value_number     | 10               |
      | cd_track            | 1                |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "1"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "10%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "15"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    When I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    Then I should not see "0%" in the ".modal" "css_element"

  Scenario: Profile field value expect greater than or equal number with tracking and keep best
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | textwithdefault  |
      | Treat value as      | Number           |
      | cd_cond_number      | Is greater than or equal to |
      | cd_value_number     | 10               |
      | cd_track            | 1                |
      | cd_keepbest         | 1                |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "1"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "textwithdefault" to "15"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    When I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    Then I should not see "0%" in the ".modal" "css_element"

  Scenario: Profile field value expect exact text
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | textlocked  |
      | Treat value as      | Text        |
      | cd_cond_text        | Is exactly  |
      | cd_value_text       | Kaboom      |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I am on the "s1" "user > editing" page logged in as "admin"
    And I expand all fieldsets
    And I set the field "textlocked" to "Tick"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I am on the "s1" "user > editing" page logged in as "admin"
    And I expand all fieldsets
    And I set the field "textlocked" to "Kaboom"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page logged in as "s1"
    When I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    Then I should not see "%" in the ".modal" "css_element"

  Scenario: Profile field value expect boolean true
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | checkbox    |
      | Treat value as      | Boolean     |
      | cd_cond_bool        | Is true     |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I click on "checkbox" "checkbox"
    And I click on "Update profile" "button"
    And I am on the "c1" "Course" page
    When I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    Then I should not see "0%" in the ".modal" "css_element"

  Scenario: Profile field value expect boolean false
    Given I click on "New objective" "button"
    And I click on "Profile field value" clickable element
    And I set the following fields to these values:
      | Profile field       | checkbox    |
      | Treat value as      | Boolean     |
      | cd_cond_bool        | Is false    |
    And I click on "Save changes" "button" in the "New objective" "dialogue"
    And the following "block_gearup > recruits" exist:
      | mission          | user   |
      | Achievement 1    | s1     |
    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "Achievement 1" "link" in the "Level Up Quest" "block"
    And I should not see "%" in the ".modal" "css_element"
