import React, { useEffect, useState } from 'react';
import $ from 'jquery';

import fieldHolder from 'components/FieldHolder/FieldHolder';
// import SingleSelectField from 'components/SingleSelectField/SingleSelectField';

import {
//     DropdownItem,
//     DropdownMenu,
//     DropdownToggle,
    Label,
    Input,
// //     InputGroup,
// //     InputGroupButton
} from 'reactstrap';

const NamedLinkFormField = (props) => {
    // `onAutofill` is a function that is passed to us by the React form builder in Silverstripe
    const { onAutofill } = props;
    // fields were passed to us from `getSchemaStateDefaults`
    const titleField = props.children[0];
    const linkmodeField = props.children[1];
    const [ linkmodeVal, updateLinkmodeVal ] = useState(linkmodeField.props.value);
    const customURLField = props.children[2];
    const [ customURLVal, updateCustomURLVal ] = useState(customURLField.props.value);
    const fileIDField = props.children[3];
    const pageIDField = props.children[4];
    const [ pageIDVal, updatePageIDVal ] = useState(pageIDField.props.value);
    const pageAnchorField = props.children[5];
    const shortcodeField = props.children[6];
    const [ shortcodeVal, updateShortcodeVal ] = useState(shortcodeField.props.value);

    // Since we're using the state from a property we need to update the
    // state when the property changes, therefore we're using `useEffect`
    useEffect(() => {
        updateCustomURLVal(customURLField.props.value);
    }, [customURLField.props.value]); // <-- here put the parameter to listen for changes on
    // When the field is changed we need to pass that up to the redux form
    useEffect(() => {
        if (typeof onAutofill !== 'function') {
            return;
        }
        // This was the function mentioned before it takes a form field name
        // and a value, this allows us to bind the field to that state when it changes.
        onAutofill(customURLField.props.name, customURLVal);
    }, [customURLVal]);

    // For Shortcode
    useEffect(() => {
        updateShortcodeVal(shortcodeField.props.value);
    }, [shortcodeField.props.value]);
    useEffect(() => {
        if (typeof onAutofill !== 'function') { return; }
        onAutofill(shortcodeField.props.name, shortcodeVal);
    }, [shortcodeVal]);

    // For Linkmode this shouldn't be necessary as this should already be handle by the component.
    // But somehow changes don't fully persist between renders without this... Maybe something with scope?)
    useEffect(() => {
        updateLinkmodeVal(linkmodeField.props.value);
    }, [linkmodeField.props.value]);
    useEffect(() => {
        if (typeof onAutofill !== 'function') { return; }
        onAutofill(linkmodeField.props.name, linkmodeVal);
    }, [linkmodeVal]);

    // For PageID, for some reason useEffect on pageIDField.props.value doesn't work (only called once/stays the same every call)
    // useEffect(() => {
    //     console.log(pageIDField.props.value)
    //     updatePageIDVal(pageIDField.props.value);
    // }, [pageIDField.props.value]);
    // Instead there seems to be a sort of onChange callback available in props, seems to be working...
    const handlePageIDupdate = (event, newPageID) => {
        let $dropdown = $(`#${pageAnchorField.props.id}`);
        $dropdown.children().not(':first').remove();
        $dropdown.attr('disabled', 'disabled');
        $.get(`admin/namedlinkpageanchors`, {
            pid: newPageID
        },
        function (data) {
            window.setTimeout(function(){
                $dropdown.removeAttr("disabled");
            }, 500);
            $.each(data, function (key, val) {
                $dropdown.append($("<option />").val(key).text(val));
            });
            // $dropdown.trigger("change");
        });
    }
    pageIDField.props.onChange = handlePageIDupdate;


    return (
        <div class={props.extraClass}>
            <div class="namedlink-row">
                <div className="fieldgroupField LinkFormFieldTitle">
                    {/*<div className="form__fieldgroup-item field field--small text">*/}
                    {/*    <Label for={titleField.props.id}>{titleField.props.title}</Label>*/}
                    {/*    <Input type="text" id={titleField.props.id} name={titleField.props.name} value={titleField.props.value}*/}
                    {/*        // onChange={(e) => setContent(e.target.value)}*/}
                    {/*    />*/}
                    {/*</div>*/}
                    {titleField}
                </div>
                <div class="fieldgroupField LinkFormFieldLinkmode" onChange={(e) => updateLinkmodeVal(e.target.value)}>
                    {/*<div class="form__fieldgroup-item field field--small text">*/}
                    {/*    <Label for={linkmodeField.props.id}>{linkmodeField.props.title}</Label>*/}
                    {/*    <Input type="select" id={linkmodeField.props.id} name={linkmodeField.props.name} value={linkmodeField.props.value}*/}
                    {/*           onChange={e => setLinkmode(e.target.value)} >*/}
                    {/*        { linkmodeField.props.source.map((item, index) => {*/}
                    {/*            const key = `${linkmodeField.props.name}-${item.value || `empty${index}`}`;*/}
                    {/*            const description = item.description || null;*/}
                    {/*            return (*/}
                    {/*                <option key={key} value={item.value} disabled={item.disabled} title={description}>*/}
                    {/*                    {item.title}*/}
                    {/*                </option>*/}
                    {/*            );*/}
                    {/*        }) }*/}
                    {/*    </Input>*/}
                    {/*</div>*/}
                    {linkmodeField}
                </div>
            </div>
            <div class="namedlink-row" className={linkmodeVal === 'Page' ? 'd-block' : 'd-none'}>
                {/*<div class="fieldgroupField LinkFormFieldPageID">*/}
                <div class="fieldgroupField LinkFormFieldPageID"
                     onChange={(e) => updatePageIDVal(e.target.value)}
                     onUpdate={handlePageIDupdate}
                >
                    {pageIDField}
                    <label class="right">(&uarr; Select Page to link to)</label>
                </div>
                <div class="fieldgroupField LinkFormFieldPageAnchor" on>
                    {pageAnchorField}
                </div>
            </div>
            <div className="namedlink-row" className={linkmodeVal === 'File' ? 'd-block' : 'd-none'}>
                <div class="fieldgroupField LinkFormFieldFileID">
                    {fileIDField}
                    <label class="right">(&uarr; Select File to link to)</label>
                </div>
            </div>
            <div className="namedlink-row" className={linkmodeVal === 'URL' || linkmodeVal === 'Email' ? 'd-block' : 'd-none'}>
                <div class="fieldgroupField LinkFormFieldCustomURL">
                    <Input type="text" id={customURLField.props.id} name={customURLField.props.name}
                           value={customURLVal} onChange={(e) => updateCustomURLVal(e.target.value)} />
                    <label class="right">(&uarr; Enter URL/E-mail)</label>
                </div>
            </div>
            <div className="namedlink-row" className={linkmodeVal === 'Shortcode' ? 'd-block' : 'd-none'}>
                <div class="fieldgroupField LinkFormFieldShortcode">
                    <Input type="text" id={shortcodeField.props.id} name={shortcodeField.props.name}
                           value={shortcodeVal} onChange={(e) => updateShortcodeVal(e.target.value)} />
                    <label class="right">(&uarr; Enter Shortcode)</label>
                </div>
            </div>
        </div>
    );
};

// `fieldHolder` wraps our field in the default Silverstripe field divs and classes.
// We use this to make our UI look consistent
export default fieldHolder(NamedLinkFormField);
