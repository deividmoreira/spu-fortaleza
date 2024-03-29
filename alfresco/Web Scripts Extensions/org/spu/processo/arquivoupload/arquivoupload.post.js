//<import resource="/Company Home/Data Dictionary/Scripts/SPU/processo.js">

var fileName = null;
var fileContent = null;
var node = null;
var nodeId = null;
var tipoDocumentoNodeRef;

for each (field in formdata.fields) {
    if (field.name == "fileToUpload" && field.isFile) {
        fileName = field.filename
        fileContent = field.content
    } else if (field.name == "destNodeUuid") {
        nodeId = field.value
    } else if (field.name == "tipoDocumento" && field.value.match(/workspace/)) {
        tipoDocumentoNodeRef = field.value
    }
}

if (fileName == undefined || fileContent == undefined) {
    status.code = 400
    status.message = "Arquivo do upload não pode ser encontrado"
    status.redirect = true
} else {
    //node = getNode(nodeId)
    node = search.findNode('workspace://SpacesStore/' + nodeId)

    var existingNode = node.childByNamePath(fileName);
   
    if (existingNode != undefined) {
        existingNode.createVersion('Update', true);
        existingNode.content = fileContent;
        existingNode.save()
    } else {
        uploadedFile = node.createFile(fileName)
        //uploadedFile.addAspect('cm:versionable') // pensar sobre versionable

        /*var props = new Array();
        props['spu:tipoDocumento.nivel1'] = 'nodeRef de category';
        props['spu:tipoDocumento.nivel2'] = 'nodeRef de category';
        props['spu:tipoDocumento.nivel3'] = 'nodeRef de category';
        uploadedFile.addAspect('spu:tipoDocumento', props);*/

        //uploadedFile.content = fileContent;

        uploadedFile.properties.content.write(fileContent);
        uploadedFile.properties.content.guessMimetype(fileName);

        if (tipoDocumentoNodeRef) {
            upFileNode = search.findNode(uploadedFile.nodeRef);
            upFileNode.properties['spu:tipo-documento'] = search.findNode(tipoDocumentoNodeRef);
            upFileNode.save();
        }
    }
}

model.fileName = fileName
model.nodeId = nodeId

