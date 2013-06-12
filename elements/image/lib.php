<?php

// This file is part of the customcert module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * The image elements core interaction API.
 *
 * @package    customcertelements_image
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/mod/customcert/elements/element.class.php');

class customcert_elements_image extends customcert_elements_base {

    /**
     * Constructor.
     *
     * @param stdClass $element the element data
     */
    function __construct($element) {
        parent::__construct($element);

        // Set the image, width and height for this element.
        $image = '';
        $width = '0';
        $height = '0';

        // Check if there is any data for this element.
        if (!empty($this->element->data)) {
            $imageinfo = json_decode($this->element->data);
            $image = $imageinfo->pathnamehash;
            $width = $imageinfo->width;
            $height = $imageinfo->height;
        }

        $this->element->image = $image;
        $this->element->width = $width;
        $this->element->height = $height;
    }

    /**
     * This function renders the form elements when adding a customcert element.
     *
     * @param stdClass $mform the edit_form instance.
     */
    public function render_form_elements($mform) {
        $mform->addElement('select', 'image', get_string('image', 'customcertelements_image'), self::get_images());

        $mform->addElement('text', 'width', get_string('width', 'customcertelements_image'), array('size' => 10));
        $mform->setType('width', PARAM_INT);
        $mform->addHelpButton('width', 'width', 'customcertelements_image');

        $mform->addElement('text', 'height', get_string('height', 'customcertelements_image'), array('size' => 10));
        $mform->setType('height', PARAM_INT);
        $mform->addHelpButton('height', 'height', 'customcertelements_image');

        parent::render_form_elements_position($mform);
    }

    /**
     * Performs validation on the element values.
     *
     * @param array $data the submitted data
     * @param array $files the submitted files
     * @return array the validation errors
     */
    public function validate_form_elements($data, $files) {
        // Array to return the errors.
        $errors = array();

        // Check if width is not set, or not numeric or less than 0.
        if ((!isset($data['width'])) || (!is_numeric($data['width'])) || ($data['width'] < 0)) {
            $errors['width'] = get_string('invalidwidth', 'customcertelements_image');
        }

        // Check if height is not set, or not numeric or less than 0.
        if ((!isset($data['height'])) || (!is_numeric($data['height'])) || ($data['height'] < 0)) {
            $errors['height'] = get_string('invalidheight', 'customcertelements_image');
        }

        // Validate the position.
        $errors += $this->validate_form_elements_position($data);

        return $errors;
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * customcert_elements table.
     *
     * @param stdClass $data the form data.
     * @return string the json encoded array
     */
    public function save_unique_data($data) {
        // Array of data we will be storing in the database.
        $arrtostore = array(
            'pathnamehash' => $data->image,
            'width' => $data->width,
            'height' => $data->height
        );

        return json_encode($arrtostore);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param stdClass $pdf the pdf object
     */
    public function render($pdf) {
        global $CFG;

        // If there is no element data, we have nothing to display.
        if (empty($this->element->data)) {
            return;
        }

        $imageinfo = json_decode($this->element->data);

        // Get the image.
        $fs = get_file_storage();
        if ($file = $fs->get_file_by_hash($imageinfo->pathnamehash)) {
            $contenthash = $file->get_contenthash();
            $l1 = $contenthash[0] . $contenthash[1];
            $l2 = $contenthash[2] . $contenthash[3];
            $location = $CFG->dataroot . '/filedir' . '/' . $l1 . '/' . $l2 . '/' . $contenthash;
            $pdf->Image($location, $this->element->posx, $this->element->posy, $imageinfo->width, $imageinfo->height);
        }
    }

    /**
     * Return the list of possible images to use.
     *
     * @return array the list of images that can be used.
     */
    public static function get_images() {
        // Create file storage object.
        $fs = get_file_storage();

        // The array used to store the images.
        $arrfiles = array();
        $arrfiles[0] = get_string('noimage', 'customcert');
        if ($files = $fs->get_area_files(context_system::instance()->id, 'mod_customcert', 'image', false, 'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$hash] = $file->get_filename();
            }
        }

        return $arrfiles;
    }
}
