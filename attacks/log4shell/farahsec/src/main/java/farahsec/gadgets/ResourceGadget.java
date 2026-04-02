package farahsec.gadgets;

import farahsec.MarshallerBase;
import farahsec.UtilFactory;

public interface ResourceGadget extends Gadget{
    @Args ( minArgs = 1, args = {
            "codebase"
    }, defaultArgs = {
            MarshallerBase.defaultCodebase
    } )
    Object makeResource (UtilFactory uf, String[] args ) throws Exception;
}
