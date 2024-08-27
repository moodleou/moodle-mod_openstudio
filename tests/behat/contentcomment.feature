@ou @ou_vle @mod @mod_openstudio @_file_upload @javascript
Feature: Add/Reply/Flag/Delete Open Studio comment
  In order to Add/Reply/Flag/Delete comment
  As a student
  I need to be able to Add/Reply/Flag/Delete comment

  Background: Setup course and studio
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category | format | numsections |
      | Course 1 | C1        | 0        | topics | 0           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |

    # Enable REST web service
    Then I am logged in as "admin"
    And the following config values are set as admin:
      | enablewebservices | 1 |
    And I navigate to "Server > Manage protocols" in site administration
    And I click on "Enable" "link" in the "REST protocol" "table_row"
    And I press "Save changes"

    And the following open studio "instances" exist:
      | course | name           | description                | pinboard | idnumber | tutorroles |
      | C1     | Sharing Studio | Sharing Studio description | 99       | OS1      | manager    |
    And the following open studio "contents" exist:
      | openstudio | user     | name           | description             | visibility |
      | OS1        | student1 | Student slot 1 | Test slot 1 description | module     |

    And all users have accepted the plagarism statement for "OS1" openstudio

  @javascript
  Scenario: Add/Reply/Flag/Delete comment

    # Add new comment
    And I am on the "Sharing Studio" "openstudio activity" page logged in as "student1"
    And I follow "Student slot 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Comment text"
    And I wait until the page is ready
    And I upload "mod/openstudio/tests/importfiles/test.mp3" file to "Attach an audio (MP3 file) as comment" filemanager
    And I press "Post comment"
    Then I should see "Comment text"

    # Flag comment
    And I follow "Like comment"
    Then I should see "1" in the ".openstudio-comment-flag-count" "css_element"
    Then I should not see "Like comment"
    And I should see "Unlike comment"

    # Unflag comment
    And I follow "Unlike comment"
    Then I should see "0" in the ".openstudio-comment-flag-status.unflagged .openstudio-comment-flag-count" "css_element"
    Then I should not see "Unlike comment"
    And I should see "Like comment"

    # Reply comment
    And I press "Reply"
    And I set the field "Comment" to "Comment text reply"
    And I press "Post comment"
    Then I should see "Comment text reply"

    # Reply other user's comment
    And I am on the "Sharing Studio" "openstudio activity" page logged in as "student2"
    And I follow "Student slot 1"
    Then I should see "Report comment"
    Then I should not see "Delete comment"
    And I reload the page
    And I press "Reply"
    And I set the field "Comment" to "Comment text reply 2"
    And I press "Post comment"
    Then I should see "Comment text reply 2"
    And I wait until the page is ready

    # Delete comment
    And I follow "Delete comment"
    And I click on "Delete" "button" in the "Delete comment" "dialogue"
    Then I should not see "Comment text reply 2"

  @javascript
  Scenario: Reply comment must be delete when the parent comment deleted in comment box
    And I am on the "Sharing Studio" "openstudio activity" page logged in as "student1"
    # Add new comment.
    And I follow "Student slot 1"
    And I press "Add new comment"
    And I set the field "Comment" to "Comment text"
    And I wait until the page is ready
    And I press "Post comment"
    # Reply comment.
    And I press "Reply"
    And I set the field "Comment" to "Comment text reply"
    And I press "Post comment"

    And I follow "Shared Content"
    When I click on "//*[@class='openstudio-grid-item-content-detail-info-icon'][1]" "xpath_element"
    # Verify the parent comment and its reply are visible in comment box.
    Then I should see "Comments on this post"
    And I should see "Comment text"
    And I should see "Comment text reply"
    And I follow "Student slot 1"
    And I follow "Delete comment"
    And I click on "Delete" "button" in the "Delete comment" "dialogue"
    And I follow "Shared Content"
    When I click on "//*[@class='openstudio-grid-item-content-detail-info-icon'][1]" "xpath_element"
    # Verify the parent comment and its reply have been deleted in comment box.
    Then I should see "There are no comments."

  @_file_upload @javascript @editor_tiny
  Scenario: Comment editor should have browse repositories.
    When I log in as "student1"
    And I follow "Private files" in the user menu
    And I upload "mod/openstudio/tests/importfiles/test2.jpg" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I am on the "Sharing Studio" "openstudio activity" page
    # Add new comment.
    And I follow "Student slot 1"
    And I press "Add new comment"
    # Upload an image.
    And I expand all toolbars for the "Comment" TinyMCE editor
    And I click on the "Image" button for the "Comment" TinyMCE editor
    And I click on "Browse repositories" "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "test2.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "How would you describe this image to someone who can't see it?" to "An image"
    And I click on "Save" "button" in the "Image details" "dialogue"
    # Post comment.
    And I press "Post comment"
    Then "//img[contains(@src, '/test2.jpg') and @alt='An image']" "xpath_element" should exist
