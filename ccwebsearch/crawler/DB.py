
#
# A DB abstraction as a Spyce Module
# Ben Adida (ben@mit.edu)
#

import psycopg
import sys
# import jon.dbpool as dbpool

# dbpool.set_database(psycopg, 10)

class db:
    conn = psycopg.connect("port=5432 dbname=cc", serialize=0)
    def __init__(self):
        pass

    def close(self):
        pass

    def _get_cursor(self):
        c= db.conn.cursor()
        c.autocommit()
        return c

    def perform(self, sql):
        db_cursor = self._get_cursor()
        db_cursor.execute(sql)
        db_cursor.commit()
        db_cursor.close()

    def oneval(self, sql):
        singlerow = self.onerow(sql)
        return singlerow[0]

    def onerow(self, sql):
        rows= self.multirow(sql)
        if len(rows) == 0:
            return None
        return rows[0]

    def multirow(self, sql):
        db_cursor = self._get_cursor()
        db_cursor.execute(sql)
        rows= self.getdict(db_cursor.fetchall(),db_cursor.description)
        db_cursor.commit()
        db_cursor.close()
        return rows

    def dbstr(self, string):
        #print 'dbstr0='+string
        #print 'dbstr1='+str(string)
	sys.stdout.flush()
        return "'"+string.replace("'","''")+"'"

    def getdict(self, results, description):
        """Returns a list of ResultRow objects based upon already retrieved results
        and the query description returned from cursor.description"""

        # get the field names
        fields = {}
        for i in range(len(description)):
            fields[description[i][0]] = i

        # generate the list of ResultRow objects
        rows = []
        for result in results:
            oneres= ResultRow(result,fields)
            rows.append(oneres)

        # return to the user
        return rows


class ResultRow:
  """A single row in a result set with a dictionary-style and list-style interface"""

  def __init__(self, row, fields):
    """Called by ResultSet function.  Don't call directly"""
    self.row = row
    self.fields = fields

  def __str__(self):
    """Returns a string representation"""
    return str(self.row)

  def __getitem__(self, key):
    """Returns the value of the named column"""
    if type(key) == type(1): # if a number
      return self.row[key]
    else:  # a field name
      return self.row[self.fields[key]]

  def __setitem__(self, key, value):
    """Not used in this implementation"""
    raise TypeError, "can't set an item of a result set"

  def __getslice__(self, i, j):
    """Returns the value of the numbered column"""
    return self.row[i: j]

  def __setslice__(self, i, j, list):
    """Not used in this implementation"""
    raise TypeError, "can't set an item of a result set"

  def keys(self):
    """Returns the field names"""
    return self.fields.keys()

  def keymappings(self):
    """Returns a dictionary of the keys and their indices in the row"""
    return self.fields

  def has_key(self, key):
    """Returns whether the given key is valid"""
    return self.fields.has_key(key)

  def __len__(self):
    """Returns how many columns are in this row"""
    return len(self.row)

