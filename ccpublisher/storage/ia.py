from cctagutils.metadata import metadata
import zope.interface
import storage.interface
import metadata.basic
import pyarchive
import interface
import metadata.interfaces

from metadata.basic import MetaElement
from metadata.interfaces import TEXT, COMBO
from license import LicenseMeta

class WorkMeta(metadata.basic.MetadataSection):
    zope.interface.implements(metadata.interfaces.ISection)
    FIELDS = [MetaElement(TEXT, 'copyrightyear', default='', tip='', label='Copyright Year'),
              MetaElement(TEXT, 'copyrightholder', default='', tip='', label='Copyright Holder'),
              MetaElement(COMBO,'format', default='Video', tip='', label='Work format', 
                          choices=['Video',
                                   'Audio',
                                   'Text',]
                          ),
              MetaElement(TEXT, 'description', default='', tip='', label='Description'),
              MetaElement(TEXT, 'keywords', default='', tip='', label='Keywords'),
              MetaElement(TEXT, 'title', default='', tip='', label='Title of Work'),
              MetaElement(TEXT, 'creator', default='', tip='', label='Creator'),
              MetaElement(TEXT, 'sourceurl', default='', tip='', label='Source URL'),
              ]
    
    def __init__(self):
        metadata.basic.MetadataSection.__init__(self,'workmeta', 'Tell Us About Your File', self.FIELDS )
    
class ArchiveMeta(metadata.basic.MetadataSection):
    zope.interface.implements(metadata.interfaces.ISection)
    FIELDS = [MetaElement(metadata.interfaces.TEXT,
                    'keywords', '', '', 'Keywords')]
    def __init__(self):
        metadata.basic.MetadataSection.__init__(self,'ameta', 'Tell Us More About Your File', self.FIELDS )
    
class ArchiveDetails(metadata.basic.MetadataSection):
    zope.interface.implements(metadata.interfaces.ISection)
    visible = False

    FIELDS = [MetaElement(metadata.interfaces.TEXT,
                    'iausername'),
              MetaElement(metadata.interfaces.TEXT,
                    'iapasswd'),]
                    
    def __init__(self):
        metadata.basic.MetadataSection.__init__(self, 'adetails', '', self.FIELDS )
    
class ArchiveStorage:
    zope.interface.implements(storage.interface.IPublisherStorage);
    
    def __init__(self, publisher):
        self.publisher = publisher
        
        self._sections = (WorkMeta(), LicenseMeta(), ArchiveMeta(), ArchiveDetails())

    def getSections(self):
        return self._sections
        
    def getField(self, key):
        """Return the first metadata field with the given key from any
        storage provider."""
        for section in self.getSections():
                try:
                    return section.getField(key)
                except KeyError:
                    pass
                    
        raise KeyError
        
    def __collection(self):
        """Determine the appropriate IA collection."""
        format = self.getField('format').getValue()
        
        if format == 'video':
            return pyarchive.const.OPENSOURCE_MOVIES
        elif format == 'audio':
            return pyarchive.const.OPENSOURCE_AUDIO
        else:
            # TODO: Raise an intelligent exception
            raise KeyError
        
    def submit(self):
        """Embed the license and upload files to archive.org"""

        # generate the identifier and make sure it's available
        archive_id = self.__archiveId()

        # determine the appropriate collection
        archive_collection = self.__collection()
        if archive_collection == pyarchive.const.OPENSOURCE_AUDIO:
            submission_type = pyarchive.const.AUDIO
        if archive_collection == pyarchive.const.OPENSOURCE_MOVIES:
            submission_type = pyarchive.const.VIDEO

        # generate the verification url
        v_url = pyarchive.identifier.verify_url(archive_collection,
                                               archive_id,
                                               submission_type)
       
        # embed the license in the file(s)
        # get form values
        license = self.getField('licenseurl').getValue()
        year = self.getField('copyrightyear').getValue()
        holder = self.getField('copyrightholder').getValue()
        
        try:
            title = self.getField('title').getValue()
        except KeyError, e:
            title = ''

        for filename in [n[0] for n in self.fileList]:
            try:
                metadata(filename).embed(license, v_url, year, holder)
            except NotImplementedError, e:
                pass

        # create the submission object
        submission = pyarchive.submission.ArchiveItem(archive_id, 
            archive_collection, submission_type,
            (title.strip() or 'untitled')
           )

        # assign any work metadata to the submission for carry over
        publisher_meta = publisher.allMeta()
        for key in publisher_meta:
           submission[key] = publisher_meta[key]

        submission['licenseurl'] = license

        for fileInfo in self.fileList:
           sub = submission.addFile(fileInfo[0], pyarchive.const.ORIGINAL,
                              claim = self.claimString(license, v_url, year, holder)
                              )
           sub.format = fileInfo[1].fileFormat

        # TODO: fix callback
        final_url = submission.submit(self.getField('iausername').getValue(),
                                      self.getField('iapasswd').getValue(),
                                      callback=submissionMetadata.callback)

        return final_url
       
    def __archiveId(self):
       """Generates an archive.org identifier from work metadata or
       embedded ID3 tags."""
       
       id_pieces = []
       try:
           id_pieces.append(self.getField('creator').getValue())
       except KeyError, e:
           pass
           
       try:
           id_pieces.append(self.getField('title').getValue())
       except KeyError, e:
           pass

       if len(id_pieces) < 2:
           id_pieces = id_pieces + [os.path.split(n[0])[-1] for n in self.fileList]
           
       archive_id = pyarchive.identifier.munge(" ".join(id_pieces))
       id_avail = pyarchive.identifier.available(archive_id)

       # if the id is not available, add a number to the end
       # and check again
       i = 0
       orig_id = archive_id
       while not(id_avail):
           archive_id = '%s_%s' % (orig_id, i)
           
           i = i + 1
           id_avail = pyarchive.identifier.available(archive_id)

       return archive_id
    

        