<?php declare(strict_types=1);

/**
 * @author            Jesús López Reyes <lopez@leifos.com>
 * @version           $Id$
 * @ilCtrl_IsCalledBy ilAppointmentPresentationCourseGUI: ilCalendarAppointmentPresentationGUI
 * @ingroup           ServicesCalendar
 */
class ilAppointmentPresentationCourseGUI extends ilAppointmentPresentationGUI implements ilCalendarAppointmentPresentation
{
    public function collectPropertiesAndActions() : void
    {
        $settings = ilCalendarSettings::_getInstance();
        $this->lng->loadLanguageModule("crs");

        $app = $this->appointment;

        $cat_info = $this->getCatInfo();

        $crs = new ilObjCourse($cat_info['obj_id'], false);
        $files = ilCourseFile::_readFilesByCourse($cat_info['obj_id']);

        // get course ref id (this is possible, since courses only have one ref id)
        $refs = ilObject::_getAllReferences($cat_info['obj_id']);
        $crs_ref_id = current($refs);

        // add common section (title, description, object/calendar, location)
        $this->addCommonSection($app, $cat_info['obj_id']);

        //count number of files
        if (count($files)) {
            $this->has_files = true;
        }
        // add specific section only if the event is autogenerated.
        if ($app['event']->isAutoGenerated()) {
            $this->addInfoSection($this->lng->txt("cal_crs_info"));

            if ($crs->getImportantInformation()) {
                $this->addInfoProperty($this->lng->txt("crs_important_info"),
                    ilUtil::makeClickable(nl2br($crs->getImportantInformation())));
            }

            if ($crs->getSyllabus()) {
                $this->addInfoProperty($this->lng->txt("crs_syllabus"),
                    ilUtil::makeClickable(nl2br($crs->getSyllabus())));
            }

            if ($this->has_files) {
                $links = array();
                foreach ($files as $file) {
                    $this->ctrl->setParameter($this, 'file_id', $file->getFileId());
                    $this->ctrl->setParameterByClass('ilobjcoursegui', 'file_id', $file->getFileId());
                    $this->ctrl->setParameterByClass('ilobjcoursegui', 'ref_id', $crs_ref_id);

                    $file_name = $file->getFileName();
                    $links[$file_name] = $this->ui->renderer()->render(($this->ui->factory()->button()->shy(
                        $file_name,
                        $this->ctrl->getLinkTargetByClass(array("ilRepositoryGUI", "ilobjcoursegui"), 'sendfile')
                    )));

                    $this->ctrl->setParameterByClass('ilobjcoursegui', 'ref_id', '');
                }
                ksort($links, SORT_NATURAL|SORT_FLAG_CASE);

                $this->addInfoProperty($this->lng->txt("files"), implode("<br>", $links));
                $this->addListItemProperty($this->lng->txt("files"), implode(", ", $links));
            }

            // tutorial support members
            $parts = ilParticipants::getInstanceByObjId($cat_info['obj_id']);
            //contacts is an array of user ids.
            $contacts = $parts->getContacts();
            $sorted_ids = ilUtil::_sortIds($contacts, 'usr_data', 'lastname', 'usr_id');

            $names = [];
            foreach ($sorted_ids as $usr_id) {
                $name_presentation = $this->getUserName($usr_id, true);
                if (strlen($name_presentation)) {
                    $names[] = $name_presentation;
                }
            }
            if (count($names)) {
                $this->addInfoProperty($this->lng->txt('crs_mem_contacts'), implode('<br/>', $names));
                $this->addListItemProperty($this->lng->txt('crs_mem_contacts'), implode('<br />', $names));
            }

            //course contact
            $contact_fields = false;
            $str = "";
            if ($crs->getContactName()) {
                $str .= $crs->getContactName() . "<br>";
            }

            if ($crs->getContactEmail()) {
                //TODO: optimize this
                //$courseGUI = new ilObjCourseGUI("", $crs_ref_id);
                $emails = explode(",", $crs->getContactEmail());
                foreach ($emails as $email) {
                    $email = trim($email);
                    $etpl = new ilTemplate("tpl.crs_contact_email.html", true, true, 'Modules/Course');
                    $etpl->setVariable(
                        "EMAIL_LINK",
                        ilMailFormCall::getLinkTarget(
                            $this->getInfoScreen(),
                            'showSummary',
                            array(),
                            array(
                                'type' => 'new',
                                'rcp_to' => $email,
                                //'sig' => $courseGUI->createMailSignature()
                            ),
                            array(
                                ilMailFormCall::CONTEXT_KEY => ilCourseMailTemplateMemberContext::ID,
                                'ref_id' => $crs->getRefId(),
                                'ts' => time()
                            )
                        )
                    );
                    $etpl->setVariable("CONTACT_EMAIL", $email);
                    $str .= $etpl->get() . "<br />";
                }
            }

            if ($crs->getContactPhone()) {
                $str .= $this->lng->txt("crs_contact_phone") . ": " . $crs->getContactPhone() . "<br>";
            }
            if ($crs->getContactResponsibility()) {
                $str .= $crs->getContactResponsibility() . "<br>";
            }
            if ($crs->getContactConsultation()) {
                $str .= $crs->getContactConsultation() . "<br>";
            }

            if ($str != "") {
                $this->addInfoProperty($this->lng->txt("crs_contact"), $str);
                $this->addListItemProperty($this->lng->txt("crs_contact"), str_replace("<br>", ", ", $str));
            }

            $this->addMetaData('crs', $cat_info['obj_id']);

            // last edited
            $this->addLastUpdate($app);
        }

        $this->addAction($this->lng->txt("crs_open"), ilLink::_getStaticLink($crs_ref_id, "crs"));

        // register/unregister to appointment
        if ($settings->isCGRegistrationEnabled()) {
            if (!$app['event']->isAutoGenerated()) {
                $reg = new ilCalendarRegistration($app['event']->getEntryId());

                if ($reg->isRegistered($this->user->getId(), new ilDateTime($app['dstart'], IL_CAL_UNIX),
                    new ilDateTime($app['dend'], IL_CAL_UNIX))) {
                    //$this->ctrl->setParameterByClass('ilcalendarappointmentgui','seed',$this->getSeed()->get(IL_CAL_DATE));
                    $this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'app_id', $app['event']->getEntryId());
                    $this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'dstart', $app['dstart']);
                    $this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'dend', $app['dend']);

                    $this->addAction($this->lng->txt('cal_reg_unregister'),
                        $this->ctrl->getLinkTargetByClass('ilcalendarappointmentgui', 'confirmUnregister'));
                } else {
                    //$this->ctrl->setParameterByClass('ilcalendarappointmentgui','seed',$this->getSeed()->get(IL_CAL_DATE));
                    $this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'app_id', $app['event']->getEntryId());
                    $this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'dstart', $app['dstart']);
                    $this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'dend', $app['dend']);

                    $this->addAction($this->lng->txt('cal_reg_register'),
                        $this->ctrl->getLinkTargetByClass('ilcalendarappointmentgui', 'confirmRegister'));
                }

                $registered = $reg->getRegisteredUsers(
                    new \ilDateTime($app['dstart'], IL_CAL_UNIX),
                    new \ilDateTime($app['dend'], IL_CAL_UNIX)
                );

                $users = "";
                foreach ($registered as $user) {
                    $users .= $this->getUserName($user) . '<br />';
                }
                if ($users != "") {
                    $this->addInfoProperty($this->lng->txt("cal_reg_registered_users"), $users);
                }
            }
        }
    }
}
