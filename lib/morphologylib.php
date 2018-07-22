<?php
/**
 * Machina morphology library
 * Contains morphology objects and functions for creating word forms
 */
defined('MACHINA_INTERNAL') || die;

// Verb constants


abstract class verb {
    
    const IRREGULAR = 0;
    const FIRST_CONJUGATION = 1;
    const SECOND_CONJUGATION = 2;
    const THIRD_CONJUGATION = 3;
    const THIRD_IO_CONJUGATION = 4;
    const FOURTH_CONJUGATION = 5;
    
    const SINGULAR = 1;
    const PLURAL = 2;
    
    const FIRST_PERSON = 3;
    const SECOND_PERSON = 4;
    const THIRD_PERSON = 5;
    
    const PRESENT_TENSE = 6;
    const IMPERFECT_TENSE = 7;
    const FUTURE_TENSE = 8;
    const PERFECT_TENSE = 9;
    const PLUPERFECT_TENSE = 10;
    const FUTURE_PERFECT_TENSE = 11;
    
    const ACTIVE_VOICE = 12;
    const PASSIVE_VOICE = 13;
    
    const INDICATIVE = 14;
    const IMPERATIVE = 15;
    const SUBJUNCTIVE = 16;

    public $pp1;
    public $pp2;
    public $pp3;
    public $pp4;
    public $verbgroup;
    public $transitive;
    public $deponent;
    public $defective;
    public static $VERBGROUPS = array(self::IRREGULAR, 
                                      self::FIRST_CONJUGATION, 
                                      self::SECOND_CONJUGATION, 
                                      self::THIRD_CONJUGATION, 
                                      self::THIRD_IO_CONJUGATION, 
                                      self::FOURTH_CONJUGATION);
                                      
    public static $NUMBERS = array(self::SINGULAR, self::PLURAL);
    
    public static $PERSONS = array(self::FIRST_PERSON, self::SECOND_PERSON, self::THIRD_PERSON);
    
    public static $PRESENTSYSTEM = array(self::PRESENT_TENSE,
                                         self::IMPERFECT_TENSE,
                                         self::FUTURE_TENSE);
                                         
    public static $PERFECTSYSTEM = array(self::PERFECT_TENSE,
                                         self::PLUPERFECT_TENSE,
                                         self::FUTURE_PERFECT_TENSE);
                                         
    public static $TENSES = array(self::PRESENT_TENSE,
                                  self::IMPERFECT_TENSE,
                                  self::FUTURE_TENSE,
                                  self::PERFECT_TENSE,
                                  self::PLUPERFECT_TENSE,
                                  self::FUTURE_PERFECT_TENSE);
    
    public static $VOICES = array(self::ACTIVE_VOICE, self::PASSIVE_VOICE);
     
    /**
     * Constructor
     * @param $verb verb object
     * @return verb object
     */
    function __construct($pp1, $pp2 = '', $pp3 = '', $pp4 = '', $verbgroup, $transitive, $deponent, $defective) {
        $this->pp1 = $pp1;
        $this->pp2 = $pp2;
        $this->pp3 = $pp3;
        $this->pp4 = $pp4;
        $this->verbgroup = $verbgroup;
        $this->transitive = $transitive;
        $this->deponent = $deponent;
        $this->defective = $defective;
    }
    
    abstract public function conjugate();
    
    public static function get_group_names($group = null, $short = false) {
        $longnames = array(self::IRREGULAR            => 'Irregular',
                       self::FIRST_CONJUGATION    => '1st Conjugation',
                       self::SECOND_CONJUGATION   => '2nd Conjugation',
                       self::THIRD_CONJUGATION    => '3rd Conjugation',
                       self::THIRD_IO_CONJUGATION => macron::convert_upper('3rd -iO Conjugation'),
                       self::FOURTH_CONJUGATION   => '4th Conjugation');
                       
        $shortnames = array(self::IRREGULAR            => 'Irr',
                       self::FIRST_CONJUGATION    => '1st',
                       self::SECOND_CONJUGATION   => '2nd',
                       self::THIRD_CONJUGATION    => '3rd',
                       self::THIRD_IO_CONJUGATION => macron::convert_upper('3rd -iO'),
                       self::FOURTH_CONJUGATION   => '4th');
                       
        if (isset($group)) {
            if (in_array($group, self::$VERBGROUPS)) {
                return $short ? $shortnames[$group] : $longnames[$group];
            } else {
                debugging('Bad group parameter');
                return '';
            }
        } else {
            return $short ? $shortnames : $longnames;    
        }
    }
    
    public static function get_tense_names($tense = null) {
        $names = array(self::PRESENT_TENSE        => 'Present Tense',
                       self::IMPERFECT_TENSE      => 'Imperfect Tense',
                       self::FUTURE_TENSE         => 'Future Tense',
                       self::PERFECT_TENSE        => 'Perfect Tense',
                       self::PLUPERFECT_TENSE     => 'Pluperfect Tense',
                       self::FUTURE_PERFECT_TENSE => 'Future Perfect Tense');
        if (isset($tense)) {
            if (in_array($tense, self::$TENSES)) {
                return $names[$tense];
            } else {
                debugging('Bad tense parameter');
                return '';
            }
        } else {
            return $names;    
        }
    }
    
    public static function get_number_names($number = null) {
        $names = array(self::SINGULAR => 'Singular',
                       self::PLURAL   => 'Plural');
        if (isset($number)) {
            if (in_array($number, self::$NUMBERS)) {
                return $names[$number];
            } else {
                debugging('Bad number parameter');
                return '';
            }
        } else {
            return $names;    
        }
    }
    
    public static function get_voice_names($voice = null) {
        $voices = array(self::ACTIVE_VOICE  => 'Active Voice',
                        self::PASSIVE_VOICE => 'Passive Voice');
        if (isset($voice)) {
            if (in_array($voice, self::$VOICES)) {
                return $voices[$voice];
            } else {
                debugging ('Bad voice parameter');
                return '';
            }
        } else {
            return $voices;
        }
    }
    
    public function get_principal_parts() {
        return "$this->pp1, $this->pp2, $this->pp3, $this->pp4";
    }
}

class regular_verb extends verb {
    public $root;
    public $present_stem;
    public $perfect_stem;
    public $participial_stem;
    
    // Define the regular verb endings
    protected $endings = array(
        parent::ACTIVE_VOICE => array(
            parent::PRESENT_TENSE => array(
                parent::FIRST_PERSON  => array(parent::SINGULAR => 'O', parent::PLURAL => 'mus'),
                parent::SECOND_PERSON => array(parent::SINGULAR => 's', parent::PLURAL => 'tis'),
                parent::THIRD_PERSON  => array(parent::SINGULAR => 't', parent::PLURAL => 'nt')
                ),
            parent::IMPERFECT_TENSE => array(
                parent::FIRST_PERSON  => array(parent::SINGULAR => 'bam', parent::PLURAL => 'bAmus'),
                parent::SECOND_PERSON => array(parent::SINGULAR => 'bAs', parent::PLURAL => 'bAtis'),
                parent::THIRD_PERSON  => array(parent::SINGULAR => 'bat', parent::PLURAL => 'bant')
                ),
            parent::FUTURE_TENSE => array(),    // Must be defined later as they depend on conjugation
            parent::PERFECT_TENSE => array(
                parent::FIRST_PERSON  => array(parent::SINGULAR => 'I', parent::PLURAL => 'imus'),
                parent::SECOND_PERSON => array(parent::SINGULAR => 'istI', parent::PLURAL => 'istis'),
                parent::THIRD_PERSON  => array(parent::SINGULAR => 'it', parent::PLURAL => 'Erunt')
                ),
            parent::PLUPERFECT_TENSE => array(
                parent::FIRST_PERSON  => array(parent::SINGULAR => 'eram', parent::PLURAL => 'erAmus'),
                parent::SECOND_PERSON => array(parent::SINGULAR => 'erAs', parent::PLURAL => 'erAtis'),
                parent::THIRD_PERSON  => array(parent::SINGULAR => 'erat', parent::PLURAL => 'erant')
                ),
            parent::FUTURE_PERFECT_TENSE => array(
                parent::FIRST_PERSON  => array(parent::SINGULAR => 'erO', parent::PLURAL => 'erimus'),
                parent::SECOND_PERSON => array(parent::SINGULAR => 'eris', parent::PLURAL => 'eritis'),
                parent::THIRD_PERSON  => array(parent::SINGULAR => 'erit', parent::PLURAL => 'erint')
                )
            ),
        parent::PASSIVE_VOICE => array(
            parent::PRESENT_TENSE => array(
                parent::FIRST_PERSON  => array(parent::SINGULAR => 'or', parent::PLURAL => 'mur'),
                parent::SECOND_PERSON => array(parent::SINGULAR => 'ris', parent::PLURAL => 'minI'),
                parent::THIRD_PERSON  => array(parent::SINGULAR => 'tur', parent::PLURAL => 'ntur')
                ),
            parent::IMPERFECT_TENSE => array(
                parent::FIRST_PERSON  => array(parent::SINGULAR => 'bAr', parent::PLURAL => 'bAmur'),
                parent::SECOND_PERSON => array(parent::SINGULAR => 'bAris', parent::PLURAL => 'bAminI'),
                parent::THIRD_PERSON  => array(parent::SINGULAR => 'bAtur', parent::PLURAL => 'bantur')
                ),
            parent::FUTURE_TENSE => array()   // Must be defined later as they depend on conjugation
            )
        );
    
    
    function __construct($pp1, $pp2, $pp3, $pp4 = '', $verbgroup, $transitive) {
        // Regular verbs are not defective or deponent
        parent::__construct($pp1, $pp2, $pp3, $pp4, $verbgroup, $transitive, false, false);
        
        $this->root = substr($this->pp2, 0, -3);
        $this->present_stem = substr($this->pp2, 0, -2);
        $this->perfect_stem = substr($this->pp3, 0, -1);
        $this->participial_stem = substr($this->pp4, 0, -2);
        
        switch ($this->verbgroup) {
            case parent::FIRST_CONJUGATION:
            case parent::SECOND_CONJUGATION:
                $this->endings[parent::ACTIVE_VOICE][parent::FUTURE_TENSE] = array(
                    parent::FIRST_PERSON  => array(parent::SINGULAR => 'bO', parent::PLURAL => 'bimus'),
                    parent::SECOND_PERSON => array(parent::SINGULAR => 'bis', parent::PLURAL => 'bitis'),
                    parent::THIRD_PERSON  => array(parent::SINGULAR => 'bit', parent::PLURAL => 'bunt'));
                $this->endings[parent::PASSIVE_VOICE][parent::FUTURE_TENSE] = array(
                    parent::FIRST_PERSON  => array(parent::SINGULAR => 'bor', parent::PLURAL => 'bimur'),
                    parent::SECOND_PERSON => array(parent::SINGULAR => 'beris', parent::PLURAL => 'biminI'),
                    parent::THIRD_PERSON  => array(parent::SINGULAR => 'bitur', parent::PLURAL => 'buntur'));
                break;
            case parent::THIRD_CONJUGATION:
            case parent::THIRD_IO_CONJUGATION:
            case parent::FOURTH_CONJUGATION:
                $this->endings[parent::ACTIVE_VOICE][parent::FUTURE_TENSE] = array(
                    parent::FIRST_PERSON  => array(parent::SINGULAR => 'am', parent::PLURAL => 'Emus'),
                    parent::SECOND_PERSON => array(parent::SINGULAR => 'Es', parent::PLURAL => 'Etis'),
                    parent::THIRD_PERSON  => array(parent::SINGULAR => 'et', parent::PLURAL => 'ent'));
                $this->endings[parent::PASSIVE_VOICE][parent::FUTURE_TENSE] = array(
                    parent::FIRST_PERSON  => array(parent::SINGULAR => 'ar', parent::PLURAL => 'Emur'),
                    parent::SECOND_PERSON => array(parent::SINGULAR => 'Eris', parent::PLURAL => 'Emini'),
                    parent::THIRD_PERSON  => array(parent::SINGULAR => 'Etur', parent::PLURAL => 'entur'));
                break;
        }
    }
    
    public function conjugate() {
        switch ($this->verbgroup) {
            case parent::FIRST_CONJUGATION:
                return $this->conjugate_first();
                break;
            case parent::SECOND_CONJUGATION:
                return $this->conjugate_second();
                break;
            case parent::THIRD_CONJUGATION:
                return $this->conjugate_third();
                break;
            case parent::THIRD_IO_CONJUGATION:
                return $this->conjugate_third_io();
                break;
            case parent::FOURTH_CONJUGATION:
                return $this->conjugate_fourth();
                break;
        }
    }
    
    /**
     * Conjugates a first conjugation verb
     */
    protected function conjugate_first() {
        $forms = array();
        foreach (parent::$VOICES as $voice) {
            foreach (parent::$TENSES as $tense) {
                if (in_array($tense, parent::$PRESENTSYSTEM)) {
                    $stem = $this->present_stem;
                } else {
                    $stem = $this->perfect_stem;
                }
                foreach (parent::$PERSONS as $person) {
                    foreach (parent::$NUMBERS as $number) {
                        if ($tense  == parent::PRESENT_TENSE) {
                            if ($person == parent::FIRST_PERSON and $number == parent::SINGULAR) {
                                $forms[$voice][$tense][$person][$number] =  array('stem' => $this->root, 'ending' => $this->endings[$voice][$tense][$person][$number]);
                                continue;
                            } else if ($person == parent::THIRD_PERSON) {
                                $forms[$voice][$tense][$person][$number] =  array('stem' => $this->root.'a', 'ending' => $this->endings[$voice][$tense][$person][$number]);
                                continue;
                            }
                        }
                        $forms[$voice][$tense][$person][$number] =  array('stem' => $stem, 'ending' => $this->endings[$voice][$tense][$person][$number]);
                    }
                }
            }
        }
        return $forms;
    }
    
    /**
     * Conjugates a second conjugation verb
     */
    protected function conjugate_second() {
        $forms = array();
        foreach (parent::$TENSES as $tense) {
            if (in_array($tense, parent::$PRESENTSYSTEM)) {
                $stem = $this->present_stem;
            } else {
                $stem = $this->perfect_stem;
            }
            foreach (parent::$PERSONS as $person) {
                foreach (parent::$NUMBERS as $number) {
                    if ($tense  == parent::PRESENT_TENSE) {
                        if (($person == parent::FIRST_PERSON and $number == parent::SINGULAR)
                            or $person == parent::THIRD_PERSON) {
                            $forms[$tense][$person][$number] =  array('stem' => $this->root.'e', 'ending' => $this->endings[$tense][$person][$number]);
                            continue;
                        }
                    }
                    $forms[$tense][$person][$number] =  array('stem' => $stem, 'ending' => $this->endings[$tense][$person][$number]);
                }
            }
        }
        return $forms;
    }
    
    /**
     * Conjugates a third conjugation verb
     */
    protected function conjugate_third() {
        $forms = array();
        foreach (parent::$TENSES as $tense) {
            if (in_array($tense, parent::$PRESENTSYSTEM)) {
                $stem = $this->present_stem;
            } else {
                $stem = $this->perfect_stem;
            }
            foreach (parent::$PERSONS as $person) {
                foreach (parent::$NUMBERS as $number) {
                    if ($tense  == parent::PRESENT_TENSE) {
                        if (($person == parent::FIRST_PERSON and $number == parent::SINGULAR)
                            or $person == parent::THIRD_PERSON) {
                            $forms[$tense][$person][$number] =  array('stem' => $this->root.'e', 'ending' => $this->endings[$tense][$person][$number]);
                            continue;
                        }
                    }
                    $forms[$tense][$person][$number] =  array('stem' => $stem, 'ending' => $this->endings[$tense][$person][$number]);
                }
            }
        }
        return $forms;
    }
    
    /**
     * Conjugates a third-io conjugation verb
     */
    protected function conjugate_third_io() {
        $forms = array();
        foreach (parent::$TENSES as $tense) {
            if (in_array($tense, parent::$PRESENTSYSTEM)) {
                $stem = $this->present_stem;
            } else {
                $stem = $this->perfect_stem;
            }
            foreach (parent::$PERSONS as $person) {
                foreach (parent::$NUMBERS as $number) {
                    if ($tense  == parent::PRESENT_TENSE) {
                        if (($person == parent::FIRST_PERSON and $number == parent::SINGULAR)
                            or $person == parent::THIRD_PERSON) {
                            $forms[$tense][$person][$number] =  array('stem' => $this->root.'e', 'ending' => $this->endings[$tense][$person][$number]);
                            continue;
                        }
                    }
                    $forms[$tense][$person][$number] =  array('stem' => $stem, 'ending' => $this->endings[$tense][$person][$number]);
                }
            }
        }
        return $forms;
    }
    
    /**
     * Conjugates a fourth conjugation verb
     */
    protected function conjugate_fourth() {
        $forms = array();
        foreach (parent::$TENSES as $tense) {
            if (in_array($tense, parent::$PRESENTSYSTEM)) {
                $stem = $this->present_stem;
            } else {
                $stem = $this->perfect_stem;
            }
            foreach (parent::$PERSONS as $person) {
                foreach (parent::$NUMBERS as $number) {
                    if ($tense  == parent::PRESENT_TENSE) {
                        if (($person == parent::FIRST_PERSON and $number == parent::SINGULAR)
                            or $person == parent::THIRD_PERSON) {
                            $forms[$tense][$person][$number] =  array('stem' => $this->root.'e', 'ending' => $this->endings[$tense][$person][$number]);
                            continue;
                        }
                    }
                    $forms[$tense][$person][$number] =  array('stem' => $stem, 'ending' => $this->endings[$tense][$person][$number]);
                }
            }
        }
        return $forms;
    }
}
