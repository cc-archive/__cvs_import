import zope.component
import zope.interface

import p6
import interfaces

@zope.interface.implementer(p6.storage.interfaces.IInputStream)
@zope.component.adapter(interfaces.IArchiveFile)
def ArchiveFileInputStream(archive_file):
    return lambda:file(archive_file.filename, 'rb')

zope.component.provideAdapter(ArchiveFileInputStream)

@zope.interface.implementer(p6.storage.interfaces.IInputStream)
@zope.component.adapter(interfaces.IArchiveWorkItem)
def ArchiveWorkInputStream(archive_item):
    return p6.storage.interfaces.IInputStream(archive_item.item)

zope.component.provideAdapter(ArchiveWorkInputStream)

