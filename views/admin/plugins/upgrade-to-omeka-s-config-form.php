<fieldset id="fieldset-archiving-process"><legend><?php echo __('Process'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('archiving_processor',
                __('Command of the xslt processor')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('This is required by some formats that need to parse a xslt 2 stylesheet.'); ?>
                <?php echo __('See format of the command and examples in the readme.'); ?>
                <?php echo __('Let empty to use the internal xslt processor of php.'); ?>
            </p>
            <?php echo $this->formText('archiving_processor', get_option('archiving_processor'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('archiving_xmllint',
                __('Command for "xmllint"')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('"xmllint" is a command line tool that is used to check xml files and to reindent them.'); ?>
                <?php echo __('This is a standard library provided by the package "libxml2-utils" in Debian or "libxml2" in Fedora.'); ?>
            </p>
            <?php echo $this->formText('archiving_xmllint', get_option('archiving_xmllint'), null); ?>
        </div>
    </div>
   <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('archiving_jpeg2000',
                __('Command for JPEG 2000')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('Command to convert jpeg 2000 to png, with "%1$s" for the source and "%2$s" for the destination.'); ?>
                <?php echo __('Let empty to use Imagick "convert" if it supports J2K.'); ?>
                <?php echo __('See an example in readme.'); ?>
            </p>
            <?php echo $this->formText('archiving_jpeg2000', get_option('archiving_jpeg2000'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('archiving_ocrmypdf',
                __('Command of OcrMyPdf')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation">
                <?php echo __('Command to convert pdf made from images into an ocerized pdf.'); ?>
            </p>
            <?php echo $this->formText('archiving_ocrmypdf', get_option('archiving_ocrmypdf'), null); ?>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-archiving-rights"><legend><?php echo __('Rights and Roles'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('archiving_allow_roles', __('Roles that can use Archiving')); ?>
        </div>
        <div class="inputs five columns omega">
            <div class="input-block">
                <ul style="list-style-type: none;">
                <?php
                    $currentRoles = unserialize(get_option('archiving_allow_roles')) ?: array();
                    $userRoles = get_user_roles();
                    foreach ($userRoles as $role => $label) {
                        echo '<li>';
                        echo $this->formCheckbox('archiving_allow_roles[]', $role,
                            array('checked' => in_array($role, $currentRoles) ? 'checked' : ''));
                        echo $label;
                        echo '</li>';
                    }
                ?>
                </ul>
            </div>
        </div>
    </div>
</fieldset>
